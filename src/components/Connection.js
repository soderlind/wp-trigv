/**
 * Connection tab — API key, default channel/level, and test send.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	Card,
	CardBody,
	TextControl,
	SelectControl,
	Button,
	Notice,
	Spinner,
	Flex,
	FlexItem,
} from '@wordpress/components';
import apiFetch from '../api';

const LEVELS = [
	{ label: 'info', value: 'info' },
	{ label: 'success', value: 'success' },
	{ label: 'warning', value: 'warning' },
	{ label: 'error', value: 'error' },
];

export default function Connection() {
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ testing, setTesting ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ settings, setSettings ] = useState( {
		has_api_key: false,
		masked_key: '',
		key_from_constant: false,
		default_channel: 'general',
		default_level: 'info',
	} );
	const [ apiKey, setApiKey ] = useState( '' );

	useEffect( () => {
		apiFetch( { path: '/trigv/v1/settings' } )
			.then( ( data ) => setSettings( data ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const save = () => {
		setSaving( true );
		setNotice( null );
		apiFetch( {
			path: '/trigv/v1/settings',
			method: 'POST',
			data: {
				api_key: apiKey,
				default_channel: settings.default_channel,
				default_level: settings.default_level,
			},
		} )
			.then( ( data ) => {
				setSettings( data );
				setApiKey( '' );
				setNotice( {
					status: 'success',
					text: __( 'Settings saved.', 'push-notifications-for-trigv' ),
				} );
			} )
			.catch( ( err ) =>
				setNotice( {
					status: 'error',
					text: err.message || __( 'Save failed.', 'push-notifications-for-trigv' ),
				} )
			)
			.finally( () => setSaving( false ) );
	};

	const sendTest = () => {
		setTesting( true );
		setNotice( null );
		apiFetch( {
			path: '/trigv/v1/test',
			method: 'POST',
			data: { channel: settings.default_channel },
		} )
			.then( () =>
				setNotice( {
					status: 'success',
					text: __( 'Test notification sent.', 'push-notifications-for-trigv' ),
				} )
			)
			.catch( ( err ) =>
				setNotice( {
					status: 'error',
					text:
						err.error ||
						err.message ||
						__( 'Test failed.', 'push-notifications-for-trigv' ),
				} )
			)
			.finally( () => setTesting( false ) );
	};

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<Card>
			<CardBody>
				{ notice && (
					<Notice
						status={ notice.status }
						onRemove={ () => setNotice( null ) }
					>
						{ notice.text }
					</Notice>
				) }

				{ settings.key_from_constant ? (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'The API key is defined via the TRIGV_API_KEY constant and cannot be changed here.',
							'push-notifications-for-trigv'
						) }
					</Notice>
				) : (
					<TextControl
						type="password"
						label={
							settings.has_api_key
								? __( 'Trigv API key (saved)', 'push-notifications-for-trigv' )
								: __( 'Trigv API key', 'push-notifications-for-trigv' )
						}
						placeholder={
							settings.has_api_key
								? settings.masked_key
								: __( 'Paste your trgv_… token.', 'push-notifications-for-trigv' )
						}
						help={
							settings.has_api_key
								? __(
										'A key is saved. Leave blank to keep it, or paste a new key to replace it.',
										'push-notifications-for-trigv'
								  )
								: __( 'Paste your trgv_… token.', 'push-notifications-for-trigv' )
						}
						value={ apiKey }
						onChange={ setApiKey }
						__next40pxDefaultSize
					/>
				) }

				<TextControl
					label={ __( 'Default channel', 'push-notifications-for-trigv' ) }
					value={ settings.default_channel }
					onChange={ ( v ) =>
						setSettings( { ...settings, default_channel: v } )
					}
					__next40pxDefaultSize
				/>

				<SelectControl
					label={ __( 'Default level', 'push-notifications-for-trigv' ) }
					value={ settings.default_level }
					options={ LEVELS }
					onChange={ ( v ) =>
						setSettings( { ...settings, default_level: v } )
					}
					__next40pxDefaultSize
				/>

				<Flex justify="flex-start" gap={ 3 }>
					<FlexItem>
						<Button
							variant="primary"
							onClick={ save }
							isBusy={ saving }
						>
							{ __( 'Save', 'push-notifications-for-trigv' ) }
						</Button>
					</FlexItem>
					<FlexItem>
						<Button
							variant="secondary"
							onClick={ sendTest }
							isBusy={ testing }
							disabled={ ! settings.has_api_key }
						>
							{ __( 'Send test', 'push-notifications-for-trigv' ) }
						</Button>
					</FlexItem>
				</Flex>
			</CardBody>
		</Card>
	);
}
