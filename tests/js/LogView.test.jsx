import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';

// @wordpress/* packages are aliased to local stubs (see vitest.config.js).
const apiFetch = vi.fn();
vi.mock( '../../src/api', () => ( { default: ( ...args ) => apiFetch( ...args ) } ) );

// Import after mocks are registered.
const { default: LogView } = await import( '../../src/components/LogView' );

describe( 'LogView', () => {
	beforeEach( () => apiFetch.mockReset() );

	it( 'shows the empty state when there are no dispatches', async () => {
		apiFetch.mockResolvedValue( { entries: [] } );

		render( <LogView /> );

		expect( await screen.findByText( 'No dispatches yet.' ) ).toBeInTheDocument();
	} );

	it( 'renders a row for each dispatch entry', async () => {
		apiFetch.mockResolvedValue( {
			entries: [
				{
					time: 1700000000,
					trigger: 'Post published',
					title: 'Hello world',
					channel: 'general',
					level: 'success',
					status: 'sent',
					http_code: 202,
				},
			],
		} );

		render( <LogView /> );

		expect( await screen.findByText( 'Hello world' ) ).toBeInTheDocument();
		expect( screen.getByText( 'sent' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Post published' ) ).toBeInTheDocument();
	} );
} );
