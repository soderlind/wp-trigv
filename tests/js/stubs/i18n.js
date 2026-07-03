export const __ = ( text ) => text;
export const sprintf = ( format, ...args ) => {
	let i = 0;
	return String( format ).replace( /%[sd]/g, () => String( args[ i++ ] ?? '' ) );
};
