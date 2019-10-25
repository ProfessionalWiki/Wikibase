--[[
	Registers and defines functions to access Wikibase through the Scribunto extension
	Provides Lua setupInterface

	@since 0.4

	@license GNU GPL v2+
	@author Jens Ohlig < jens.ohlig@wikimedia.de >
	@author Marius Hoch < hoo@online.de >
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local wikibase = {}
local util = require 'libraryUtil'
local checkType = util.checkType
local checkTypeMulti = util.checkTypeMulti

local maxEntityCacheSize = 15 -- Size of the LRU cache being used to cache entities
local entityCache = {}

-- Cache a given value (can also be false, in case it doesn't exist).
--
-- @param cache
-- @param maxCacheSize
-- @param key
-- @param value
local addToCache = function( cache, maxCacheSize, key, value )
	if type( cache.data ) ~= 'table' then
		cache.data = {}
		cache.order = {}
	end

	if #cache.order == maxCacheSize then
		local toRemove = table.remove( cache.order, maxCacheSize )
		cache[ toRemove ] = nil
	end

	table.insert( cache.order, 1, key )
	cache[ key ] = value
end

-- Retrieve a value from a cache. Will return nil in case of a cache miss.
--
-- @param cache
-- @param key
local getFromCache = function( cache, key )
	if cache[ key ] ~= nil then
		for cacheOrderId, cacheOrderKey in pairs( cache.order ) do
			if cacheOrderKey == key then
				table.remove( cache.order, cacheOrderId )
				break
			end
		end
		table.insert( cache.order, 1, key )
	end

	return cache[ key ]
end

-- Cache a given entity (can also be false, in case it doesn't exist).
--
-- @param entityId
-- @param entity
local cacheEntity = function( entityId, entity )
	addToCache( entityCache, maxEntityCacheSize, entityId, entity )
end

-- Retrieve an entity. Will return false in case it's known to not exist
-- and nil in case of a cache miss.
--
-- @param entityId
local getCachedEntity = function( entityId )
	return getFromCache( entityCache, entityId )
end

function wikibase.setupInterface()
	local php = mw_interface
	mw_interface = nil

	-- Caching variable for the entity id string belonging to the current page (nil if page is not linked to an entity)
	local pageEntityId = false

	-- Get the entity id of the connected item, if id is nil. Cached.
	local getIdOfConnectedItemIfNil = function( id )
		if id == nil then
			return wikibase.getEntityIdForCurrentPage()
		end

		return id
	end

	-- Get the mw.wikibase.entity object for a given id. Cached.
	local getEntityObject = function( id )
		local entity = getCachedEntity( id )

		if entity == nil then
			entity = php.getEntity( id )

			if id ~= wikibase.getEntityIdForCurrentPage() then
				-- Accessing an arbitrary entity is supposed to increment the expensive function count
				php.incrementExpensiveFunctionCount()
			end

			if type( entity ) ~= 'table' then
				entity = false
			end

			cacheEntity( id, entity )
		end

		if type( entity ) ~= 'table' then
			return nil
		end

		local entityModule = require( php.getEntityModuleName( id ) )

		-- Use a deep clone here, so that people can't modify the entity
		return entityModule.create( mw.clone( entity ) )
	end

	-- Get the entity id for the current page. Cached.
	-- Nil if not linked to an entity.
	wikibase.getEntityIdForCurrentPage = function()
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getEntityIdForCurrentPage.call' )

		if pageEntityId == false then
			pageEntityId = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )
		end

		return pageEntityId
	end

	-- Takes a page title string either in the local wiki or another wiki on the same cluster
	-- specified by the global site identifier, and returns the item ID connected via a sitelink, if
	-- one exists. Returns nil if there's no linked item.
	--
	-- @param {string} pageTitle
	-- @param {string} [globalSiteId]
	wikibase.getEntityIdForTitle = function( pageTitle, globalSiteId )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getEntityIdForTitle.call' )

		checkType( 'getEntityIdForTitle', 1, pageTitle, 'string' )
		checkTypeMulti( 'getEntityIdForTitle', 2, globalSiteId, { 'string', 'nil' } )

		return php.getEntityId( pageTitle, globalSiteId )
	end

	-- Get the mw.wikibase.entity object for the current page or for the
	-- specified id.
	--
	-- @param {string} [id]
	wikibase.getEntity = function( id )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getEntity.call' )

		checkTypeMulti( 'getEntity', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil
		end

		if not php.getSetting( 'allowArbitraryDataAccess' ) and id ~= wikibase.getEntityIdForCurrentPage() then
			error( 'Access to arbitrary entities has been disabled.', 2 )
		end

		return getEntityObject( id )
	end

	-- getEntityObject is an alias for getEntity as these used to be different.
	wikibase.getEntityObject = wikibase.getEntity

	-- @param {string} entityId
	-- @param {string} propertyId
	-- @param {string} funcName for error logging
	-- @param {string} rank Which statements to include. Either "best" or "all".
	local getEntityStatements = function( entityId, propertyId, funcName, rank )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getEntityStatements.call' )

		if not php.getSetting( 'allowArbitraryDataAccess' ) and entityId ~= wikibase.getEntityIdForCurrentPage() then
			error( 'Access to arbitrary entities has been disabled.', 2 )
		end

		checkType( funcName, 1, entityId, 'string' )
		checkType( funcName, 2, propertyId, 'string' )

		local statements = php.getEntityStatements( entityId, propertyId, rank )
		if statements and statements[propertyId] then
			return statements[propertyId]
		end

		return {}
	end

	-- Returns a table with the "best" statements matching the given property ID on the given entity
	-- ID. The definition of "best" is that the function will return "preferred" statements, if
	-- there are any, otherwise "normal" ranked statements. It will never return "deprecated"
	-- statements. This is what you usually want when surfacing values to an ordinary reader.
	--
	-- @param {string} entityId
	-- @param {string} propertyId
	wikibase.getBestStatements = function( entityId, propertyId )
		return getEntityStatements( entityId, propertyId, 'getBestStatements', 'best' )
	end

	-- Returns a table with all statements (including all ranks, even "deprecated") matching the
	-- given property ID on the given entity ID.
	--
	-- @param {string} entityId
	-- @param {string} propertyId
	wikibase.getAllStatements = function( entityId, propertyId )
		return getEntityStatements( entityId, propertyId, 'getAllStatements', 'all' )
	end

	-- Get the URL for the given entity id, if specified, or of the
	-- connected entity, if exists.
	--
	-- @param {string} [id]
	wikibase.getEntityUrl = function( id )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getEntityUrl.call' )

		checkTypeMulti( 'getEntityUrl', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil
		end

		return php.getEntityUrl( id )
	end

	-- Get the label, label language for the given entity id, if specified,
	-- or of the connected entity, if exists.
	--
	-- @param {string} [id]
	wikibase.getLabelWithLang = function( id )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getLabelWithLang.call' )

		checkTypeMulti( 'getLabelWithLang', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil, nil
		end

		return php.getLabel( id )
	end

	-- Like wikibase.getLabelWithLang, but only returns the plain label.
	--
	-- @param {string} [id]
	wikibase.getLabel = function( id )
		checkTypeMulti( 'getLabel', 1, id, { 'string', 'nil' } )
		local label = wikibase.getLabelWithLang( id )

		return label
	end

	-- Legacy alias for getLabel
	wikibase.label = wikibase.getLabel

	-- Get the label in languageCode for the given entity id.
	--
	-- @param {string} id
	-- @param {string} languageCode
	wikibase.getLabelByLang = function( id, languageCode )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getLabelByLang.call' )

		checkType( 'getLabelByLang', 1, id, 'string' )
		checkType( 'getLabelByLang', 2, languageCode, 'string' )

		return php.getLabelByLanguage( id, languageCode )
	end

	-- Get the description, description language for the given entity id, if specified,
	-- or of the connected entity, if exists.
	--
	-- @param {string} [id]
	wikibase.getDescriptionWithLang = function( id )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getDescriptionWithLang.call' )

		checkTypeMulti( 'getDescriptionWithLang', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil, nil
		end

		return php.getDescription( id )
	end

	-- Like wikibase.getDescriptionWithLang, but only returns the plain description.
	--
	-- @param {string} [id]
	wikibase.getDescription = function( id )
		checkTypeMulti( 'getDescription', 1, id, { 'string', 'nil' } )
		local description = wikibase.getDescriptionWithLang( id )

		return description
	end

	-- Legacy alias for getDescription
	wikibase.description = wikibase.getDescription

	-- Get the local sitelink title for the given entity id.
	--
	-- @param {string} itemId
	-- @param {string} [globalSiteId]
	wikibase.getSitelink = function( itemId, globalSiteId )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getSitelink.call' )

		checkType( 'getSitelink', 1, itemId, 'string' )
		checkTypeMulti( 'getSitelink', 2, globalSiteId, { 'string', 'nil' } )

		return php.getSiteLinkPageName( itemId, globalSiteId )
	end

	-- Legacy alias for getSitelink
	wikibase.sitelink = wikibase.getSitelink

	-- Is this a valid (parseable) entity id?
	--
	-- @param {string} entityIdSerialization
	wikibase.isValidEntityId = function( entityIdSerialization )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.isValidEntityId.call' )

		checkType( 'isValidEntityId', 1, entityIdSerialization, 'string' )

		return php.isValidEntityId( entityIdSerialization )
	end

	-- Does the entity in question exist?
	--
	-- @param {string} entityId
	wikibase.entityExists = function( entityId )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.entityExists.call' )

		checkType( 'entityExists', 1, entityId, 'string' )

		if not php.getSetting( 'allowArbitraryDataAccess' ) and entityId ~= wikibase.getEntityIdForCurrentPage() then
			error( 'Access to arbitrary entities has been disabled.', 2 )
		end

		return php.entityExists( entityId )
	end

	-- Render a Snak value from its serialization as wikitext escaped plain text.
	--
	-- @param {table} snakSerialization
	wikibase.renderSnak = function( snakSerialization )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.renderSnak.call' )

		checkType( 'renderSnak', 1, snakSerialization, 'table' )

		return php.renderSnak( snakSerialization )
	end

	-- Render a Snak value from its serialization as rich wikitext.
	--
	-- @param {table} snakSerialization
	wikibase.formatValue = function( snakSerialization )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.formatValue.call' )

		checkType( 'formatValue', 1, snakSerialization, 'table' )

		return php.formatValue( snakSerialization )
	end

	-- Render a list of Snak values from their serialization as wikitext escaped plain text.
	--
	-- @param {table} snaksSerialization
	wikibase.renderSnaks = function( snaksSerialization )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.renderSnaks.call' )

		checkType( 'renderSnaks', 1, snaksSerialization, 'table' )

		return php.renderSnaks( snaksSerialization )
	end

	-- Render a list of Snak values from their serialization as rich wikitext.
	--
	-- @param {table} snaksSerialization
	wikibase.formatValues = function( snaksSerialization )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.formatValues.call' )

		checkType( 'formatValues', 1, snaksSerialization, 'table' )

		return php.formatValues( snaksSerialization )
	end

	-- Returns a property id for the given label or id
	--
	-- @param {string} propertyLabelOrId
	wikibase.resolvePropertyId = function( propertyLabelOrId )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.resolvePropertyId.call' )

		checkType( 'resolvePropertyId', 1, propertyLabelOrId, 'string' )

		return php.resolvePropertyId( propertyLabelOrId )
	end

	-- Returns a table of the given property IDs ordered
	--
	-- @param {table} propertyIds
	wikibase.orderProperties = function( propertyIds )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.orderProperties.call' )

		checkType( 'orderProperties', 1, propertyIds, 'table' )
		return php.orderProperties( propertyIds )
	end

	-- Returns an ordered table of serialized property IDs
	wikibase.getPropertyOrder = function()
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getPropertyOrder.call' )

		return php.getPropertyOrder()
	end

	-- Get the closest referenced entity (out of toIds), from a given entity.
	--
	-- @param {string} fromEntityId
	-- @param {string} propertyId
	-- @param {table} toIds
	wikibase.getReferencedEntityId = function( fromEntityId, propertyId, toIds )
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getReferencedEntityId.call' )

		checkType( 'getReferencedEntityId', 1, fromEntityId, 'string' )
		checkType( 'getReferencedEntityId', 2, propertyId, 'string' )
		checkType( 'getReferencedEntityId', 3, toIds, 'table' )

		-- Check the type of all toId values, Scribunto has no function for this yet (T196048)
		for i, toId in ipairs( toIds ) do
			if type( toId ) ~= 'string' then
				error(
					'toIds value at index ' .. i .. ' must be string, ' .. type( toId ) .. ' given.',
					1
				)
			end
		end

		if not php.getSetting( 'allowArbitraryDataAccess' ) then
			error( 'Access to arbitrary entities has been disabled.', 2 )
		end

		return php.getReferencedEntityId( fromEntityId, propertyId, toIds )
	end

	-- Returns the current site's global id
	wikibase.getGlobalSiteId = function()
		php.incrementStatsKey( 'wikibase.client.scribunto.wikibase.getGlobalSiteId.call' )

		return php.getSetting( 'siteGlobalID' )
	end

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
