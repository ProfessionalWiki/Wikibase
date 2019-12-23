# Hooks PHP {#topic_hooks-php}

This file describes hooks defined by the Wikibase extensions. See docs/hooks.txt in the MediaWiki installation root for general information on hooks.

## Repo

* 'WikibaseRepoDataTypes'
  * Called when constructing the top-level WikibaseRepo factory; May be used to define additional data types. See also the WikibaseClientDataTypes hook.
    * &$dataTypeDefinitions
      * the array of data type definitions, as defined by WikibaseRepo.datatypes.php.
  * Hook handlers may add additional definitions. See the datatypes.wiki file for details.

* 'WikibaseRepoEntityTypes'
  * Called when constructing the top-level WikibaseRepo factory; May be used to define additional entity types. See also the WikibaseClientEntityTypes hook.
    * &$entityTypeDefinitions
      * the array of entity type definitions, as defined by WikibaseLib.entitytypes.php.
  * Hook handlers may add additional definitions. See the entitytypes.wiki file for details.

* 'WikibaseTextForSearchIndex'
  * Called by EntityContent::getTextForSearchIndex() to allow extra text to be passed to the search engine for indexing. If the hook function returns false, no text at all will be passed to the search index.
    * $entity
      * EntityContent to be indexed
    * &$text
      * The text to pass to the indexed (to be modified).

* 'WikibaseContentModelMapping'
  * Called by WikibaseRepo::getContentModelMappings() to allow additional mappings between Entity types and content model identifiers to be defined.
    * &$map
      * An associative array mapping Entity types to content model ids.

* 'WikibaseRepoEntityNamespaces'
  * Called by WikibaseRepo::getEntityNamespaceLookup() to allow additional mappings between Entity types and namespace IDs to be defined.
    * &$map
      * An associative array mapping Entity types to namespace ids.

* 'WikibaseClientEntityNamespaces'
  * Called by WikibaseClient::getEntityNamespaceLookup() to allow additional mappings between Entity types and namespace IDs to be defined.
    * &$map
      * An associative array mapping Entity types to namespace ids.

* 'WikibaseRebuildData' (''DEPRECATED'')
  * Used by rebuildAllData.
    * $report
      * A closure that can be called with a string to report that messages.

* 'WikibaseDeleteData' (''DEPRECATED'')
  * Used by deleteAllData.
    * $report
      * A closure that can be called with a string to report that messages.

* 'WikibaseChangeNotification'
  * Triggered from ChangeNotifier via a HookChangeTransmitter to notify any listeners of changes to entities.
    * $change
      * The Change object representing the change.
  * For performance reasons, does not include statement, description and alias diffs (see T113468, T163465).

* 'WikibaseContentLanguages'
  * Called by WikibaseRepo::getContentLanguages(), which in turn is called by some other getters, to define the content languages per context.
    * &$map
      * An associative array mapping contexts ('term', 'monolingualtext', extension-specific…) to ContentLanguage objects.

* 'GetEntityContentModelForTitle'
  * Called by EntityContentFactory to see what is the entity content type of the Title. Extensions can override it so entity content type does not equal page content type.
    * $title
      * Title object for the page
    * &$contentType
      * Content type for the page. Extensions can override this.

## Client

* 'WikibaseClientDataTypes'
  * Called when constructing the top-level WikibaseClient factory; May be used to define additional data types. See also the WikibaseRepoDataTypes hook.
    * &$dataTypeDefinitions
      * The array of data type definitions, as defined by WikibaseClient.datatypes.php.
  * Hook handlers may add additional definitions. See the datatypes.wiki file for details.

* 'WikibaseClientEntityTypes'
  * Called when constructing the top-level WikibaseClient factory; May be used to define additional entity types. See also the WikibaseRepoEntityTypes hook.
    * &$entityTypeDefinitions
      * The array of entity type definitions, as defined by WikibaseLib.entitytypes.php.
  * Hook handlers may add additional definitions. See the entitytypes.wiki file for details.

* 'WikibaseHandleChanges'
  * Called by ChangeHandler::handleChange() to allow pre-processing of changes.
    * $changes
      * A list of Change objects
    * $rootJobParams
      * Any relevant root job parameters to be inherited by child jobs.

* 'WikibaseHandleChange'
  * Called by ChangeHandler::handleChange() to allow alternative processing of changes.
    * $change
      * A Change object
    * $rootJobParams
      * Any relevant root job parameters to be inherited by child jobs.

* 'WikibaseClientOtherProjectsSidebar'
  * Called by OtherProjectsSidebarGenerator to allow altering the other projects sidebar. Only called in case the page we're on is linked with an item.
    * $itemId
      * Id of the item the page is linked with.
    * &$newSidedbar
      * Array containing the sidebar definition. The array consists of arrays indexed by site groups containing arrays indexed by site id. These arrays represent the link to the given site. They contain the keys “msg”, “href” and “class” which contain the respective attributes for the link that is going to be created.