/**
 * apiFetch is already configured by WordPress core (the `wp-api-fetch` script)
 * with the site's REST root URL and a self-refreshing `wp_rest` nonce. We must
 * NOT add our own root-URL/nonce middleware — doing so stacks a second root URL
 * and corrupts request URLs. Just use full REST paths like `/trigv/v1/...`.
 */
import apiFetch from '@wordpress/api-fetch';

export default apiFetch;
