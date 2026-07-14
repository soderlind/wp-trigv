/**
 * Log tab — recent dispatches.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Card, CardBody, Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '../api';
import { formatLogTime } from '../utils';

export default function LogView() {
	const [ loading, setLoading ] = useState( true );
	const [ entries, setEntries ] = useState( [] );

	const load = () => {
		setLoading( true );
		apiFetch( { path: '/trigv/v1/log' } )
			.then( ( data ) => setEntries( data.entries || [] ) )
			.finally( () => setLoading( false ) );
	};

	useEffect( load, [] );

	const clear = () => {
		apiFetch( { path: '/trigv/v1/log', method: 'DELETE' } ).then( () =>
			setEntries( [] )
		);
	};

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<Card>
			<CardBody>
				{ entries.length === 0 ? (
					<Notice status="info" isDismissible={ false }>
						{ __( 'No dispatches yet.', 'push-notifications-for-trigv' ) }
					</Notice>
				) : (
					<table className="widefat striped trigv-log">
						<thead>
							<tr>
								<th>{ __( 'Time', 'push-notifications-for-trigv' ) }</th>
								<th>{ __( 'Trigger', 'push-notifications-for-trigv' ) }</th>
								<th>{ __( 'Title', 'push-notifications-for-trigv' ) }</th>
								<th>{ __( 'Channel', 'push-notifications-for-trigv' ) }</th>
								<th>{ __( 'Level', 'push-notifications-for-trigv' ) }</th>
								<th>{ __( 'Status', 'push-notifications-for-trigv' ) }</th>
								<th>{ __( 'Detail', 'push-notifications-for-trigv' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ entries.map( ( e, i ) => (
								<tr key={ i }>
									<td>{ formatLogTime( e.time ) }</td>
									<td>{ e.trigger }</td>
									<td>{ e.title }</td>
									<td>{ e.channel }</td>
									<td>{ e.level }</td>
									<td>
										<span
											className={ `trigv-status trigv-status--${ e.status }` }
										>
											{ e.status }
										</span>
									</td>
									<td>
										{ e.error ||
											( e.http_code
												? `HTTP ${ e.http_code }`
												: '' ) }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }

				<p>
					<Button
						variant="secondary"
						onClick={ clear }
						disabled={ entries.length === 0 }
					>
						{ __( 'Clear log', 'push-notifications-for-trigv' ) }
					</Button>
				</p>
			</CardBody>
		</Card>
	);
}
