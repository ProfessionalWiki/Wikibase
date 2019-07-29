import { mutations } from '@/store/mutations';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import Application from '@/store/Application';
import newApplicationState from './newApplicationState';
import ApplicationStatus from '@/store/ApplicationStatus';

describe( 'root/mutations', () => {
	it( 'changes the targetProperty of the store', () => {
		const store: Application = newApplicationState();
		mutations[ PROPERTY_TARGET_SET ]( store, 'P42' );
		expect( store.targetProperty ).toBe( 'P42' );
	} );

	it( 'changes the editFlow of the store', () => {
		const store: Application = newApplicationState();
		mutations[ EDITFLOW_SET ]( store, 'Heraklid' );
		expect( store.editFlow ).toBe( 'Heraklid' );
	} );

	it( 'changes the applicationStatus of the store', () => {
		const store: Application = newApplicationState();
		mutations[ APPLICATION_STATUS_SET ]( store, ApplicationStatus.READY );
		expect( store.applicationStatus ).toBe( ApplicationStatus.READY );
	} );
} );