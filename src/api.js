/**
 * Configured apiFetch bound to the trigv/v1 REST namespace.
 */
import apiFetch from '@wordpress/api-fetch';

const { restUrl, nonce } = window.trigvData || {};

apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
apiFetch.use( apiFetch.createRootURLMiddleware( `${ restUrl }/` ) );

export default apiFetch;
