/**
 * Root component with tabbed navigation.
 */
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import Connection from './components/Connection';
import Triggers from './components/Triggers';
import LogView from './components/LogView';

export default function App() {
	return (
		<div className="trigv-admin">
			<h1>{ __( 'Trigv', 'push-notifications-for-trigv' ) }</h1>
			<TabPanel
				className="trigv-tabs"
				tabs={ [
					{
						name: 'connection',
						title: __( 'Connection', 'push-notifications-for-trigv' ),
					},
					{ name: 'triggers', title: __( 'Triggers', 'push-notifications-for-trigv' ) },
					{ name: 'log', title: __( 'Log', 'push-notifications-for-trigv' ) },
				] }
			>
				{ ( tab ) => {
					if ( tab.name === 'connection' ) {
						return <Connection />;
					}
					if ( tab.name === 'triggers' ) {
						return <Triggers />;
					}
					return <LogView />;
				} }
			</TabPanel>
		</div>
	);
}
