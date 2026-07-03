/**
 * Triggers tab — enable Triggers and configure their Notifications.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	ToggleControl,
	TextControl,
	SelectControl,
	Button,
	Notice,
	Spinner,
	__experimentalText as Text,
} from '@wordpress/components';
import apiFetch from '../api';
import { groupByGroup } from '../utils';

const LEVELS = [
	{ label: 'info', value: 'info' },
	{ label: 'success', value: 'success' },
	{ label: 'warning', value: 'warning' },
	{ label: 'error', value: 'error' },
];

/**
 * Pre-fill empty title/description with each Trigger's default template so the
 * fields show editable default content rather than an empty placeholder.
 */
function hydrate( list ) {
	return list.map( ( t ) => ( {
		...t,
		config: {
			...t.config,
			title: t.config.title || t.default_title,
			description: t.config.description || t.default_description,
		},
	} ) );
}

export default function Triggers() {
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ triggers, setTriggers ] = useState( [] );

	useEffect( () => {
		apiFetch( { path: '/trigv/v1/triggers' } )
			.then( ( data ) => setTriggers( hydrate( data.triggers || [] ) ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const updateConfig = ( id, key, value ) => {
		setTriggers( ( prev ) =>
			prev.map( ( t ) =>
				t.id === id ? { ...t, config: { ...t.config, [ key ]: value } } : t
			)
		);
	};

	const save = () => {
		setSaving( true );
		setNotice( null );
		const payload = {};
		triggers.forEach( ( t ) => {
			payload[ t.id ] = t.config;
		} );
		apiFetch( { path: '/trigv/v1/triggers', method: 'POST', data: { triggers: payload } } )
			.then( ( data ) => {
				setTriggers( hydrate( data.triggers || [] ) );
				setNotice( { status: 'success', text: __( 'Triggers saved.', 'wp-trigv' ) } );
			} )
			.catch( ( err ) =>
				setNotice( { status: 'error', text: err.message || __( 'Save failed.', 'wp-trigv' ) } )
			)
			.finally( () => setSaving( false ) );
	};

	if ( loading ) {
		return <Spinner />;
	}

	const groups = groupByGroup( triggers );

	return (
		<div className="trigv-triggers">
			{ notice && (
				<Notice status={ notice.status } onRemove={ () => setNotice( null ) }>
					{ notice.text }
				</Notice>
			) }

			{ Object.keys( groups ).map( ( group ) => (
				<Card key={ group } className="trigv-group">
					<CardHeader>
						<Text weight={ 600 }>{ group }</Text>
					</CardHeader>
					<CardBody>
						{ groups[ group ].map( ( t ) => (
							<div key={ t.id } className="trigv-trigger">
								<ToggleControl
									label={ t.label }
									checked={ !! t.config.enabled }
									onChange={ ( v ) => updateConfig( t.id, 'enabled', v ) }
									__nextHasNoMarginBottom
								/>

								{ t.config.enabled && (
									<div className="trigv-trigger__config">
										<TextControl
											label={ __( 'Channel', 'wp-trigv' ) }
											placeholder={ __( 'Use default channel', 'wp-trigv' ) }
											value={ t.config.channel }
											onChange={ ( v ) => updateConfig( t.id, 'channel', v ) }
											__next40pxDefaultSize
										/>
										<SelectControl
											label={ __( 'Level', 'wp-trigv' ) }
											value={ t.config.level }
											options={ LEVELS }
											onChange={ ( v ) => updateConfig( t.id, 'level', v ) }
											__next40pxDefaultSize
										/>
										<TextControl
											label={ __( 'Title', 'wp-trigv' ) }
											placeholder={ t.default_title }
											value={ t.config.title }
											onChange={ ( v ) => updateConfig( t.id, 'title', v ) }
											__next40pxDefaultSize
										/>
										<TextControl
											label={ __( 'Description', 'wp-trigv' ) }
											placeholder={ t.default_description }
											value={ t.config.description }
											onChange={ ( v ) => updateConfig( t.id, 'description', v ) }
											__next40pxDefaultSize
										/>
										<ToggleControl
											label={ __( 'Time-sensitive delivery', 'wp-trigv' ) }
											checked={ !! t.config.time_sensitive }
											onChange={ ( v ) => updateConfig( t.id, 'time_sensitive', v ) }
											__nextHasNoMarginBottom
										/>
										<p className="trigv-tokens">
											{ __( 'Tokens:', 'wp-trigv' ) }{ ' ' }
											{ Object.keys( t.tokens )
												.map( ( token ) => `{${ token }}` )
												.join( ', ' ) }
										</p>
									</div>
								) }
							</div>
						) ) }
					</CardBody>
				</Card>
			) ) }

			<Button variant="primary" onClick={ save } isBusy={ saving }>
				{ __( 'Save triggers', 'wp-trigv' ) }
			</Button>
		</div>
	);
}
