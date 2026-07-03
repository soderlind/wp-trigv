/**
 * Trigv admin app entry point.
 */
import { createRoot } from '@wordpress/element';
import App from './App';
import './index.scss';

const root = document.getElementById( 'trigv-admin-root' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
