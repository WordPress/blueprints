( function ( wp, settings ) {
	if ( ! wp || ! wp.components || ! wp.element || ! wp.domReady || ! settings ) {
		return;
	}

	const { Button, Modal, Notice, PanelBody, SelectControl, TextControl } = wp.components;
	const { createElement: el, render, useState } = wp.element;
	const { strings } = settings;

	function PlaygroundWelcomeModal() {
		const [ displayName, setDisplayName ] = useState( '' );
		const [ feedUrl, setFeedUrl ] = useState( '' );
		const [ maxItems, setMaxItems ] = useState( '10' );
		const [ notice, setNotice ] = useState( null );
		const [ isSubmitting, setIsSubmitting ] = useState( false );

		function goHome() {
			window.location.href = settings.homeUrl;
		}

		function submitForm( event ) {
			event.preventDefault();
			setIsSubmitting( true );
			setNotice( null );

			const formData = new window.FormData();
			formData.append( 'action', 'playground_welcome_save' );
			formData.append( 'nonce', settings.nonce );
			formData.append( 'display_name', displayName );
			formData.append( 'feed_url', feedUrl );
			formData.append( 'max_items', maxItems );

			window
				.fetch( settings.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} )
				.then( ( response ) => response.json() )
				.then( ( response ) => {
					if ( response.success ) {
						setNotice( {
							status: 'success',
							message: response.data.message,
						} );
						window.setTimeout( goHome, 1500 );
						return;
					}

					setNotice( {
						status: 'error',
						message: response.data.message || strings.errorMessage,
					} );
					setIsSubmitting( false );
				} )
				.catch( () => {
					setNotice( {
						status: 'error',
						message: strings.errorMessage,
					} );
					setIsSubmitting( false );
				} );
		}

		return el(
			Modal,
			{
				title: strings.title,
				className: 'playground-welcome-modal',
				isDismissible: false,
				shouldCloseOnClickOutside: false,
				shouldCloseOnEsc: false,
				onRequestClose: goHome,
			},
			el(
				'form',
				{
					className: 'playground-welcome-form',
					onSubmit: submitForm,
				},
				el( 'p', { className: 'playground-welcome-intro' }, strings.intro ),
				el( TextControl, {
					label: strings.displayNameLabel,
					value: displayName,
					onChange: setDisplayName,
					autoFocus: true,
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
				} ),
				el(
					PanelBody,
					{
						title: strings.importTitle,
						initialOpen: false,
						className: 'playground-welcome-import-panel',
					},
					el(
						'div',
						{ className: 'playground-welcome-import-fields' },
						el( TextControl, {
							label: strings.feedUrlLabel,
							help: strings.feedUrlHelp,
							placeholder: 'example.com',
							value: feedUrl,
							onChange: ( value ) => {
								setFeedUrl( value );
								if ( notice && notice.status === 'error' ) {
									setNotice( null );
								}
							},
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: strings.maxItemsLabel,
							value: maxItems,
							options: [
								{ label: strings.fivePosts, value: '5' },
								{ label: strings.tenPosts, value: '10' },
								{ label: strings.twentyPosts, value: '20' },
								{ label: strings.fiftyPosts, value: '50' },
							],
							onChange: setMaxItems,
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				notice &&
					el(
						Notice,
						{
							status: notice.status,
							isDismissible: false,
						},
						notice.message
					),
				el(
					'div',
					{ className: 'playground-welcome-actions' },
					el(
						Button,
						{
							variant: 'secondary',
							href: settings.homeUrl,
							disabled: isSubmitting,
						},
						strings.notNow
					),
					el(
						Button,
						{
							variant: 'primary',
							type: 'submit',
							isBusy: isSubmitting,
							disabled: isSubmitting,
						},
						isSubmitting ? strings.importing : strings.continue
					)
				)
			)
		);
	}

	wp.domReady( function () {
		const root = document.getElementById( 'playground-welcome-root' );
		if ( root ) {
			render( el( PlaygroundWelcomeModal ), root );
		}
	} );
} )( window.wp, window.playgroundWelcomeSettings );
