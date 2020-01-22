import { storiesOf } from '@storybook/vue';
import UserLink from '../src/presentation/components/UserLink';

const router = {
	getPageUrl( title ) {
		return `https://www.wikidata.org/wiki/${title}`;
	},
};

storiesOf( 'UserLink', module )
	.addParameters( { component: UserLink } )
	.add( 'with link to user page', () => ( {
		data() {
			return {
				userId: 2799899,
				userName: 'MediaWiki default',
				router,
			};
		},
		components: { UserLink },
		template:
			`<p>This action was performed by
				<UserLink
					:userId="userId"
					:userName="userName"
					:router="router"
				/>.</p>
			`,
	} ) )

	.add( 'without link to user page', () => ( {
		data() {
			return {
				userId: 0,
				userName: 'Meta-Wiki Welcome',
				router,
			};
		},
		components: { UserLink },
		template:
			`<p>This action was performed by
				<UserLink
					:userId="userId"
					:userName="userName"
					:router="router"
				/>.</p>
			`,
	} ) )

	.add( 'bidirectionality behavior', () => ( {
		data() {
			return {
				userId: 1536453,
				userName: 'علاء',
				router,
			};
		},
		components: { UserLink },
		template:
			`<dl>
				<dt>With <code>UserLink</code></dt>
				<dd>
					<UserLink
						:userId="userId"
						:userName="userName"
						:router="router"
					/>: 1st place.
				</dd>
				<dt>Without <code>UserLink</code></dt>
				<dd>
					{{ userName }}: 1st place.
				</dd>
			</dl>`,
	} ) );
