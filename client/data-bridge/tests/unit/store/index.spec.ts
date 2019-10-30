import { createStore } from '@/store';
import newMockServiceContainer from '../services/newMockServiceContainer';

describe( 'store/index', () => {
	it( 'creates the store', () => {
		const store = createStore(
			newMockServiceContainer( {
				readingEntityRepository: {},
				writingEntityRepository: {},
				entityLabelRepository: {},
				wikibaseRepoConfigRepository: {},
			} ),
		);
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
		expect( store.state.targetProperty ).toBe( '' );
		expect( store.state.editFlow ).toBe( '' );
	} );
} );
