import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';

const stub = ( p ) => fileURLToPath( new URL( p, import.meta.url ) );

export default defineConfig( {
	resolve: {
		alias: {
			// WordPress packages are provided by WP at runtime, not installed.
			'@wordpress/element': stub( './tests/js/stubs/element.js' ),
			'@wordpress/components': stub( './tests/js/stubs/components.jsx' ),
			'@wordpress/i18n': stub( './tests/js/stubs/i18n.js' ),
		},
	},
	// Source .js files contain JSX; treat them as automatic-runtime JSX.
	// Vite excludes .js from esbuild by default, so override exclude too.
	esbuild: {
		include: [ /src\/.*\.jsx?$/, /tests\/js\/.*\.jsx?$/ ],
		exclude: [ /node_modules/ ],
		loader: 'jsx',
		jsx: 'automatic',
	},
	optimizeDeps: {
		esbuildOptions: {
			loader: { '.js': 'jsx' },
		},
	},
	test: {
		environment: 'jsdom',
		globals: true,
		setupFiles: [ './tests/js/setup.js' ],
		include: [ 'tests/js/**/*.test.{js,jsx}' ],
	},
} );
