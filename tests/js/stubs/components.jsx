/* Minimal stand-ins for the @wordpress/components used by the admin app. */
export const Card = ( { children } ) => <div>{ children }</div>;
export const CardBody = ( { children } ) => <div>{ children }</div>;
export const CardHeader = ( { children } ) => <div>{ children }</div>;
export const Button = ( { children, ...rest } ) => <button { ...rest }>{ children }</button>;
export const Spinner = () => <span data-testid="spinner">loading</span>;
export const Notice = ( { children } ) => <div>{ children }</div>;
export const Flex = ( { children } ) => <div>{ children }</div>;
export const FlexItem = ( { children } ) => <div>{ children }</div>;
export const TabPanel = ( { children, tabs } ) => <div>{ children( tabs[ 0 ] ) }</div>;
export const TextControl = ( { label, value, onChange } ) => (
	<label>
		{ label }
		<input value={ value } onChange={ ( e ) => onChange( e.target.value ) } />
	</label>
);
export const SelectControl = ( { label, value, options = [], onChange } ) => (
	<label>
		{ label }
		<select value={ value } onChange={ ( e ) => onChange( e.target.value ) }>
			{ options.map( ( o ) => (
				<option key={ o.value } value={ o.value }>
					{ o.label }
				</option>
			) ) }
		</select>
	</label>
);
export const ToggleControl = ( { label, checked, onChange } ) => (
	<label>
		{ label }
		<input type="checkbox" checked={ checked } onChange={ ( e ) => onChange( e.target.checked ) } />
	</label>
);
export const __experimentalText = ( { children } ) => <span>{ children }</span>;
