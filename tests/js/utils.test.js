import { describe, it, expect } from 'vitest';
import { groupByGroup, formatLogTime } from '../../src/utils';

describe( 'groupByGroup', () => {
	it( 'groups items by their group property', () => {
		const items = [
			{ id: 'a', group: 'Content' },
			{ id: 'b', group: 'Users' },
			{ id: 'c', group: 'Content' },
		];

		const grouped = groupByGroup( items );

		expect( Object.keys( grouped ) ).toEqual( [ 'Content', 'Users' ] );
		expect( grouped.Content ).toHaveLength( 2 );
		expect( grouped.Users[ 0 ].id ).toBe( 'b' );
	} );

	it( 'returns an empty object for no items', () => {
		expect( groupByGroup( [] ) ).toEqual( {} );
	} );
} );

describe( 'formatLogTime', () => {
	it( 'returns empty string for falsy timestamps', () => {
		expect( formatLogTime( 0 ) ).toBe( '' );
		expect( formatLogTime( undefined ) ).toBe( '' );
	} );

	it( 'formats a timestamp into a non-empty string', () => {
		const formatted = formatLogTime( 1700000000, 'en-US' );
		expect( typeof formatted ).toBe( 'string' );
		expect( formatted.length ).toBeGreaterThan( 0 );
	} );
} );
