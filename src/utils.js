/**
 * Pure helpers shared by the admin components.
 */

/**
 * Group an array of Triggers by their `group` property.
 *
 * @param {Array<{group:string}>} items Triggers.
 * @return {Object<string,Array>} Map of group name to Triggers.
 */
export function groupByGroup( items ) {
	return items.reduce( ( acc, item ) => {
		( acc[ item.group ] = acc[ item.group ] || [] ).push( item );
		return acc;
	}, {} );
}

/**
 * Format a Unix timestamp (seconds) for display.
 *
 * @param {number} ts       Unix timestamp in seconds.
 * @param {string} [locale] Optional locale.
 * @return {string} Localized date-time string, or '' when falsy.
 */
export function formatLogTime( ts, locale ) {
	if ( ! ts ) {
		return '';
	}
	return new Date( ts * 1000 ).toLocaleString( locale );
}
