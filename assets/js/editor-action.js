( function registerFluentCrmAction( wp, JetFBActions, actionData, jfb ) {
	if ( ! wp || ! JetFBActions || ! JetFBActions.addAction ) {
		return;
	}

	const { addAction } = JetFBActions;
	const { Fragment, createElement } = wp.element;
	const {
		SelectControl,
		CheckboxControl,
		TextControl,
		FormLabeledTokenField,
	} = wp.components;
	const { __ } = wp.i18n;

	const jetFormBuilder = jfb || {};
	const jfbComponents = jetFormBuilder.components || {};
	const jfbActions = jetFormBuilder.actions || {};
	const jfbBlocks = jetFormBuilder.blocksToActions || {};

	const StyledSelect = jfbComponents.StyledSelectControl || SelectControl;
	const StyledText = jfbComponents.StyledTextControl || TextControl;
	const SwitcherControl = jfbComponents.SwitcherControl || CheckboxControl;
	const LabelComponent = jfbComponents.Label || null;
	const HelpComponent = jfbComponents.Help || null;
	const TableListStyle = jfbComponents.TableListStyle || null;
	const TableListContainer = jfbActions.TableListContainer || null;
	const TableListHead = jfbActions.TableListHead || null;
	const TableListRow = jfbActions.TableListRow || null;
	const useFields = typeof jfbBlocks.useFields === 'function' ? jfbBlocks.useFields : null;

	const listOptions = Array.isArray( actionData.lists ) ? actionData.lists : [];
	const tagOptions = Array.isArray( actionData.tags ) ? actionData.tags : [];

	const ensureArrayOfStrings = ( value ) => {
		if ( Array.isArray( value ) ) {
			return value
				.filter( ( item ) => item !== null && item !== undefined && item !== '' )
				.map( String );
		}

		if ( value === null || value === undefined || value === '' ) {
			return [];
		}

		return [ String( value ) ];
	};

	const uniqueStrings = ( values ) => Array.from( new Set( values ) );

	const tokenSuggestionsFromOptions = ( options ) =>
		Array.isArray( options )
			? options
					.map( ( option ) => {
						if ( ! option ) {
							return null;
						}

						const optionValue =
							option.value !== undefined && option.value !== null ? String( option.value ) : '';

						if ( '' === optionValue ) {
							return null;
						}

						return {
							value: optionValue,
							label:
								option.label !== undefined && option.label !== null && option.label !== ''
									? option.label
									: optionValue,
						};
					} )
					.filter( Boolean )
			: [];

	const toBoolean = ( value ) => {
		if ( typeof value === 'boolean' ) {
			return value;
		}

		if ( typeof value === 'string' ) {
			return value === '1' || value.toLowerCase() === 'true';
		}

		return Boolean( value );
	};

	const getLabelValue = ( labelFn, key, fallback ) => {
		try {
			if ( typeof labelFn === 'function' ) {
				const maybe = labelFn( key );
				if ( maybe ) {
					return maybe;
				}
			}
		} catch ( e ) {
			// Ignore label helper errors and fall back to provided strings.
		}
		if ( actionData.__labels && actionData.__labels[ key ] ) {
			return actionData.__labels[ key ];
		}
		return fallback;
	};

	const getHelpValue = ( key, fallback ) => {
		if ( actionData.__help_messages && actionData.__help_messages[ key ] ) {
			return actionData.__help_messages[ key ];
		}
		return fallback;
	};

	const enrichFieldMapEntry = ( entry, labelFn ) => {
		const suffix = entry.key ? `fields_map_${ entry.key }` : null;
		return {
			...entry,
			label:
				entry.label ||
				( suffix && getLabelValue( labelFn, suffix ) ) ||
				entry.key,
			help:
				entry.help ||
				( suffix && getHelpValue( suffix ) ) ||
				'',
		};
	};

	const defaultFieldMapConfig = [
		{
			key: 'email',
			label: __( 'Email field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the subscriber email address.', 'fluent-subs-for-jetformbuilder' ),
			required: true,
		},
		{
			key: 'first_name',
			label: __( 'First name field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the subscriber first name (optional).', 'fluent-subs-for-jetformbuilder' ),
			required: false,
		},
		{
			key: 'last_name',
			label: __( 'Last name field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the subscriber last name (optional).', 'fluent-subs-for-jetformbuilder' ),
			required: false,
		},
		{
			key: 'phone',
			label: __( 'Phone field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the subscriber phone number (optional).', 'fluent-subs-for-jetformbuilder' ),
			required: false,
		},
	];

	const selectControlProps = {
		__nextHasNoMarginBottom: true,
		__next40pxDefaultSize: true,
	};

const textControlProps = {
	__nextHasNoMarginBottom: true,
	__next40pxDefaultSize: true,
};

const switcherControlProps = {
	__nextHasNoMarginBottom: true,
	__next40pxDefaultSize: true,
};

const tokenFieldProps = {
	__nextHasNoMarginBottom: true,
	__next40pxDefaultSize: true,
};

	const buildFieldMapConfig = ( labelFn ) => ( Array.isArray( actionData.field_map ) && actionData.field_map.length
		? actionData.field_map
		: defaultFieldMapConfig
	).map( ( entry ) => enrichFieldMapEntry( entry, labelFn ) );

	const FieldMapControl = ( { fieldsMap, onChangeField, labelFn } ) => {
		const fieldMapConfig = buildFieldMapConfig( labelFn );
		const fieldOptions = useFields
			? useFields( { withInner: false, placeholder: '--' } )
			: [ { value: '', label: '--' } ];

		const handleFieldChange = ( key, value ) => {
			onChangeField( key, value );
		};

		const mapLabel = getLabelValue(
			labelFn,
			'fields_map',
			__( 'Fields map', 'fluent-subs-for-jetformbuilder' )
		);
		const mapHelp = getHelpValue(
			'fields_map',
			__(
				'Select which JetFormBuilder fields supply the subscriber contact information.',
				'fluent-subs-for-jetformbuilder'
			)
		);

		const renderTableLayout =
			TableListContainer && TableListHead && TableListRow && TableListStyle;

		if ( renderTableLayout ) {
			return createElement(
				'div',
				{ className: TableListStyle.Wrap },
				LabelComponent
					? createElement(
						LabelComponent,
						{ className: TableListStyle.Label },
						mapLabel
					)
					: createElement(
						'span',
						{ className: TableListStyle.Label },
						mapLabel
					),
				HelpComponent
					? createElement(
						HelpComponent,
						{ className: TableListStyle.WhiteSpaceNormal },
						mapHelp
					)
					: createElement(
						'p',
						{ className: 'description' },
						mapHelp
					),
				createElement(
					TableListContainer,
					null,
					createElement( TableListHead, {
						columns: [
							__( 'FluentCRM field', 'fluent-subs-for-jetformbuilder' ),
							__( 'Form field', 'fluent-subs-for-jetformbuilder' ),
						],
					} ),
					fieldMapConfig.map( ( definition ) =>
						createElement(
							TableListRow,
							{
								key: definition.key,
								tag: definition.key,
								label: definition.label,
								help: definition.help,
								isRequired: !! definition.required,
							},
							( { setShowError, htmlId } ) =>
								createElement( StyledSelect, {
									...selectControlProps,
									id: htmlId,
									value: fieldsMap[ definition.key ] || '',
									options: fieldOptions,
									formFields: fieldOptions,
									onChange: ( newValue ) =>
										handleFieldChange( definition.key, newValue ),
									onBlur: () =>
										typeof setShowError === 'function' &&
										setShowError( true ),
								} )
						)
					)
				)
			);
		}

		return createElement(
			'div',
			{ className: 'jet-form-builder-fieldset jet-form-builder-fieldset--fluentcrm-map' },
			createElement(
				'strong',
				{ className: 'jet-form-builder-fieldset__title' },
				mapLabel
			),
			createElement(
				'p',
				{ className: 'description' },
				mapHelp
			),
			fieldMapConfig.map( ( definition ) =>
				createElement( StyledText, {
					...textControlProps,
					key: definition.key,
					label: definition.label,
					value: fieldsMap[ definition.key ] || '',
					onChange: ( newValue ) =>
						handleFieldChange( definition.key, newValue ),
					help: definition.help,
				} )
			)
		);
	};

	const SetupFieldset = ( {
		labelFn,
		selectedLists,
		selectedTags,
		onChangeSetting,
	} ) => {
	const listSuggestions = tokenSuggestionsFromOptions( listOptions );
	const tagSuggestions = tokenSuggestionsFromOptions( tagOptions );
	const TokenFieldComponent = jfbComponents.FormLabeledTokenField || FormLabeledTokenField || null;

		const handleListTokens = ( tokens ) => {
			onChangeSetting( uniqueStrings( ensureArrayOfStrings( tokens ) ), 'list_id' );
		};

		const handleTagTokens = ( tokens ) => {
			onChangeSetting( uniqueStrings( ensureArrayOfStrings( tokens ) ), 'tag_id' );
		};

	return createElement(
		'fieldset',
			{
				className: 'jet-form-builder-fieldset jet-form-builder-fieldset--fluentcrm-setup',
			},
			createElement(
				'div',
				{ className: 'jet-form-builder-fieldset__row jet-form-builder-fieldset__row--grid' },
				TokenFieldComponent
					? createElement( TokenFieldComponent, {
						...tokenFieldProps,
						label: getLabelValue(
							labelFn,
							'list_id',
							__( 'FluentCRM Lists', 'fluent-subs-for-jetformbuilder' )
						),
						value: selectedLists,
						onChange: handleListTokens,
						suggestions: listSuggestions,
						__experimentalExpandOnFocus: true,
						help: getHelpValue( 'list_id' ),
					} )
					: createElement( StyledSelect, {
						...selectControlProps,
						label: getLabelValue(
							labelFn,
							'list_id',
							__( 'FluentCRM Lists', 'fluent-subs-for-jetformbuilder' )
						),
						value: selectedLists,
						onChange: ( newValue ) =>
							onChangeSetting(
								uniqueStrings( ensureArrayOfStrings( newValue ) ),
								'list_id'
							),
						options: listOptions,
						help: getHelpValue( 'list_id' ),
						multiple: true,
					} ),
				TokenFieldComponent
					? createElement( TokenFieldComponent, {
						...tokenFieldProps,
						label: getLabelValue(
							labelFn,
							'tag_id',
							__( 'Tags', 'fluent-subs-for-jetformbuilder' )
						),
						value: selectedTags,
						onChange: handleTagTokens,
						suggestions: tagSuggestions,
						__experimentalExpandOnFocus: true,
						help: getHelpValue( 'tag_id' ),
					} )
					: createElement( StyledSelect, {
						...selectControlProps,
						label: getLabelValue(
							labelFn,
							'tag_id',
							__( 'Tags', 'fluent-subs-for-jetformbuilder' )
						),
						value: selectedTags,
						onChange: ( newValue ) =>
							onChangeSetting(
								uniqueStrings( ensureArrayOfStrings( newValue ) ),
								'tag_id'
							),
						options: tagOptions,
						help: getHelpValue( 'tag_id' ),
						multiple: true,
					} )
			),
			createElement( 'hr', {
				className: 'jet-form-builder-separator',
				'aria-hidden': true,
			} )
		);
	};

addAction(
	'fluentcrm_subscribe',
	function FluentCrmSubscribe( props ) {
		const { settings, label, onChangeSetting } = props;

		const selectedLists = uniqueStrings( ensureArrayOfStrings( settings.list_id ) );
		const selectedTags = uniqueStrings( ensureArrayOfStrings( settings.tag_id ) );
		const bypassOptIn = toBoolean( settings.bypass_double_optin );
		const fieldsMap = {
			email: '',
			first_name: '',
			last_name: '',
			...( settings.fields_map || {} ),
		};
		const addOnly = toBoolean( settings.add_only );
		const alreadySubscribedMessage = settings.already_subscribed_message || '';
		const existingContactMessage = settings.existing_contact_message || '';

		const updateFieldMap = ( key, value ) => {
			onChangeSetting(
				{
					...fieldsMap,
					[ key ]: value,
				},
				'fields_map'
			);
		};

		return createElement(
			Fragment,
			null,
			createElement( SetupFieldset, {
				labelFn: label,
				selectedLists,
				selectedTags,
				onChangeSetting,
			} ),
			createElement( FieldMapControl, {
				fieldsMap,
				onChangeField: updateFieldMap,
				labelFn: label,
			} ),
			createElement( StyledText, {
				...textControlProps,
				label: getLabelValue(
					label,
					'already_subscribed_message',
					__( 'Message if already subscribed', 'fluent-subs-for-jetformbuilder' )
				),
				value: alreadySubscribedMessage,
				onChange: ( newValue ) => onChangeSetting( newValue, 'already_subscribed_message' ),
				help: getHelpValue(
					'already_subscribed_message',
					__( 'Displayed when the email already belongs to a confirmed FluentCRM contact and Add only is enabled.', 'fluent-subs-for-jetformbuilder' )
				),
			} ),
			createElement( StyledText, {
				...textControlProps,
				label: getLabelValue(
					label,
					'existing_contact_message',
					__( 'Message if contact data updated', 'fluent-subs-for-jetformbuilder' )
				),
				value: existingContactMessage,
				onChange: ( newValue ) => onChangeSetting( newValue, 'existing_contact_message' ),
				help: getHelpValue(
					'existing_contact_message',
					__( 'Displayed when an existing FluentCRM contact is updated because Add only is disabled.', 'fluent-subs-for-jetformbuilder' )
				),
			} ),
			createElement( SwitcherControl, {
				...switcherControlProps,
				label: getLabelValue(
					label,
					'bypass_double_optin',
					__( 'Bypass double opt-in', 'fluent-subs-for-jetformbuilder' )
				),
				checked: bypassOptIn,
				onChange: ( newValue ) => onChangeSetting( newValue, 'bypass_double_optin' ),
				help:
					getHelpValue( 'bypass_double_optin' ) ||
					__(
						'If enabled, FluentCRM will skip the confirmation email and subscribe immediately.',
						'fluent-subs-for-jetformbuilder'
					),
			} ),
			createElement( SwitcherControl, {
				...switcherControlProps,
				label: getLabelValue(
					label,
					'add_only',
					__( 'Add only', 'fluent-subs-for-jetformbuilder' )
				),
				checked: addOnly,
				onChange: ( newValue ) => onChangeSetting( newValue, 'add_only' ),
				help:
					getHelpValue( 'add_only' ) ||
					__(
						'When enabled, existing contacts remain unchanged and only new subscriptions are processed.',
						'fluent-subs-for-jetformbuilder'
					),
			} )
	);
	},
	{
		category: 'communication',
		docHref: 'https://github.com/Lonsdale201/fluentcrm-subscriptions-for-jetformbuilder',
	}
);

	if ( wp?.data?.dispatch ) {
		try {
			wp.data.dispatch( 'jet-forms/actions' ).registerAction( {
				type: 'fluentcrm_subscribe',
				label: __( 'FluentCRM Subscribe', 'fluent-subs-for-jetformbuilder' ),
				category: 'communication',
				docHref: 'https://github.com/Lonsdale201/fluentcrm-subscriptions-for-jetformbuilder',
			} );
		} catch ( err ) {
			// Do nothing if Jet Form Builder store is not ready yet.
		}
	}
}( window.wp || false, window.JetFBActions || false, window.JetFluentCrmSubscribe || {}, window.jfb || {} ));

/* ================================================================
 * FluentCRM Add List & Tags – JetFormBuilder action editor
 * ================================================================ */
( function registerFluentCrmAddListTagsAction( wp, JetFBActions, actionData, jfb ) {
	if ( ! wp || ! JetFBActions || ! JetFBActions.addAction ) {
		return;
	}

	const { addAction } = JetFBActions;
	const { Fragment, createElement } = wp.element;
	const {
		SelectControl,
		CheckboxControl,
		TextControl,
		FormLabeledTokenField,
	} = wp.components;
	const { __ } = wp.i18n;

	const jetFormBuilder = jfb || {};
	const jfbComponents = jetFormBuilder.components || {};
	const jfbActions = jetFormBuilder.actions || {};
	const jfbBlocks = jetFormBuilder.blocksToActions || {};

	const StyledSelect = jfbComponents.StyledSelectControl || SelectControl;
	const StyledText = jfbComponents.StyledTextControl || TextControl;
	const SwitcherControl = jfbComponents.SwitcherControl || CheckboxControl;
	const LabelComponent = jfbComponents.Label || null;
	const HelpComponent = jfbComponents.Help || null;
	const TableListStyle = jfbComponents.TableListStyle || null;
	const TableListContainer = jfbActions.TableListContainer || null;
	const TableListHead = jfbActions.TableListHead || null;
	const TableListRow = jfbActions.TableListRow || null;
	const TokenFieldComponent = jfbComponents.FormLabeledTokenField || FormLabeledTokenField || null;
	const useFields = typeof jfbBlocks.useFields === 'function' ? jfbBlocks.useFields : null;

	const listOptions = Array.isArray( actionData.lists ) ? actionData.lists : [];
	const tagOptions = Array.isArray( actionData.tags ) ? actionData.tags : [];
	const statusOptions = Array.isArray( actionData.statuses ) ? actionData.statuses : [];

	const ensureArrayOfStrings = ( value ) => {
		if ( Array.isArray( value ) ) {
			return value
				.filter( ( item ) => item !== null && item !== undefined && item !== '' )
				.map( String );
		}
		if ( value === null || value === undefined || value === '' ) {
			return [];
		}
		return [ String( value ) ];
	};

	const uniqueStrings = ( values ) => Array.from( new Set( values ) );

	const tokenSuggestionsFromOptions = ( options ) =>
		Array.isArray( options )
			? options
					.map( ( option ) => {
						if ( ! option ) {
							return null;
						}
						const optionValue =
							option.value !== undefined && option.value !== null
								? String( option.value )
								: '';
						if ( '' === optionValue ) {
							return null;
						}
						return {
							value: optionValue,
							label: option.label || optionValue,
						};
					} )
					.filter( Boolean )
			: [];

	const toBoolean = ( value ) => {
		if ( typeof value === 'boolean' ) {
			return value;
		}
		if ( typeof value === 'string' ) {
			return value === '1' || value.toLowerCase() === 'true';
		}
		return Boolean( value );
	};

	const getLabelValue = ( labelFn, key, fallback ) => {
		try {
			if ( typeof labelFn === 'function' ) {
				const maybe = labelFn( key );
				if ( maybe ) {
					return maybe;
				}
			}
		} catch ( e ) {
			// fall through
		}
		if ( actionData.__labels && actionData.__labels[ key ] ) {
			return actionData.__labels[ key ];
		}
		return fallback;
	};

	const getHelpValue = ( key, fallback ) => {
		if ( actionData.__help_messages && actionData.__help_messages[ key ] ) {
			return actionData.__help_messages[ key ];
		}
		return fallback;
	};

	const selectControlProps = {
		__nextHasNoMarginBottom: true,
		__next40pxDefaultSize: true,
	};

	const textControlProps = {
		__nextHasNoMarginBottom: true,
		__next40pxDefaultSize: true,
	};

	const switcherControlProps = {
		__nextHasNoMarginBottom: true,
		__next40pxDefaultSize: true,
	};

	const tokenFieldProps = {
		__nextHasNoMarginBottom: true,
		__next40pxDefaultSize: true,
	};

	const statusSelectOptions = [
		{ value: '', label: __( '— Select status —', 'fluent-subs-for-jetformbuilder' ) },
		...statusOptions,
	];

	const defaultFieldMapConfig = [
		{
			key: 'email',
			label: __( 'Email field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the email address.', 'fluent-subs-for-jetformbuilder' ),
			required: true,
		},
		{
			key: 'first_name',
			label: __( 'First name field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the contact first name (optional).', 'fluent-subs-for-jetformbuilder' ),
			required: false,
		},
		{
			key: 'last_name',
			label: __( 'Last name field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the contact last name (optional).', 'fluent-subs-for-jetformbuilder' ),
			required: false,
		},
		{
			key: 'phone',
			label: __( 'Phone field', 'fluent-subs-for-jetformbuilder' ),
			help: __( 'JetFormBuilder field that stores the contact phone number (optional).', 'fluent-subs-for-jetformbuilder' ),
			required: false,
		},
	];

	const enrichFieldMapEntry = ( entry, labelFn ) => {
		const suffix = entry.key ? `fields_map_${ entry.key }` : null;
		return {
			...entry,
			label:
				entry.label ||
				( suffix && getLabelValue( labelFn, suffix ) ) ||
				entry.key,
			help:
				entry.help ||
				( suffix && getHelpValue( suffix ) ) ||
				'',
		};
	};

	const buildFieldMapConfig = ( labelFn ) =>
		( Array.isArray( actionData.field_map ) && actionData.field_map.length
			? actionData.field_map
			: defaultFieldMapConfig
		).map( ( entry ) => enrichFieldMapEntry( entry, labelFn ) );

	const FieldMapControl = ( { fieldsMap, onChangeField, labelFn } ) => {
		const fieldMapConfig = buildFieldMapConfig( labelFn );
		const fieldOptions = useFields
			? useFields( { withInner: false, placeholder: '--' } )
			: [ { value: '', label: '--' } ];

		const mapLabel = getLabelValue(
			labelFn,
			'fields_map',
			__( 'Fields map', 'fluent-subs-for-jetformbuilder' )
		);
		const mapHelp = getHelpValue(
			'fields_map',
			__(
				'These fields are only used when a new contact is created in FluentCRM. Existing contacts are not updated — only lists and tags are assigned.',
				'fluent-subs-for-jetformbuilder'
			)
		);

		const renderTableLayout =
			TableListContainer && TableListHead && TableListRow && TableListStyle;

		if ( renderTableLayout ) {
			return createElement(
				'div',
				{ className: TableListStyle.Wrap },
				LabelComponent
					? createElement(
							LabelComponent,
							{ className: TableListStyle.Label },
							mapLabel
					  )
					: createElement(
							'span',
							{ className: TableListStyle.Label },
							mapLabel
					  ),
				HelpComponent
					? createElement(
							HelpComponent,
							{ className: TableListStyle.WhiteSpaceNormal },
							mapHelp
					  )
					: createElement( 'p', { className: 'description' }, mapHelp ),
				createElement(
					TableListContainer,
					null,
					createElement( TableListHead, {
						columns: [
							__( 'FluentCRM field', 'fluent-subs-for-jetformbuilder' ),
							__( 'Form field', 'fluent-subs-for-jetformbuilder' ),
						],
					} ),
					fieldMapConfig.map( ( definition ) =>
						createElement(
							TableListRow,
							{
								key: definition.key,
								tag: definition.key,
								label: definition.label,
								help: definition.help,
								isRequired: !! definition.required,
							},
							( { setShowError, htmlId } ) =>
								createElement( StyledSelect, {
									...selectControlProps,
									id: htmlId,
									value: fieldsMap[ definition.key ] || '',
									options: fieldOptions,
									formFields: fieldOptions,
									onChange: ( newValue ) =>
										onChangeField( definition.key, newValue ),
									onBlur: () =>
										typeof setShowError === 'function' &&
										setShowError( true ),
								} )
						)
					)
				)
			);
		}

		return createElement(
			'div',
			{ className: 'jet-form-builder-fieldset jet-form-builder-fieldset--fluentcrm-map' },
			createElement(
				'strong',
				{ className: 'jet-form-builder-fieldset__title' },
				mapLabel
			),
			createElement( 'p', { className: 'description' }, mapHelp ),
			fieldMapConfig.map( ( definition ) =>
				createElement( StyledText, {
					...textControlProps,
					key: definition.key,
					label: definition.label,
					value: fieldsMap[ definition.key ] || '',
					onChange: ( newValue ) =>
						onChangeField( definition.key, newValue ),
					help: definition.help,
				} )
			)
		);
	};

	addAction(
		'fluentcrm_add_list_tags',
		function FluentCrmAddListTags( props ) {
			const { settings, label, onChangeSetting } = props;

			const selectedLists = uniqueStrings( ensureArrayOfStrings( settings.list_id ) );
			const selectedTags = uniqueStrings( ensureArrayOfStrings( settings.tag_id ) );
			const useCurrentUser = toBoolean( settings.use_current_user );
			const skipNonExisting = toBoolean( settings.skip_non_existing );
			const newContactStatus = settings.new_contact_status || 'subscribed';
			const fieldsMap = {
				email: '',
				first_name: '',
				last_name: '',
				phone: '',
				...( settings.fields_map || {} ),
			};

			const listSuggestions = tokenSuggestionsFromOptions( listOptions );
			const tagSuggestions = tokenSuggestionsFromOptions( tagOptions );

			const updateFieldMap = ( key, value ) => {
				onChangeSetting(
					{ ...fieldsMap, [ key ]: value },
					'fields_map'
				);
			};

			return createElement(
				Fragment,
				null,
				/* ---------- Lists & Tags side-by-side ---------- */
				createElement(
					'fieldset',
					{
						className:
							'jet-form-builder-fieldset jet-form-builder-fieldset--fluentcrm-setup',
					},
					createElement(
						'div',
						{
							className:
								'jet-form-builder-fieldset__row jet-form-builder-fieldset__row--grid',
						},
						TokenFieldComponent
							? createElement( TokenFieldComponent, {
									...tokenFieldProps,
									label: __( 'FluentCRM Lists', 'fluent-subs-for-jetformbuilder' ),
									value: selectedLists,
									onChange: ( tokens ) =>
										onChangeSetting(
											uniqueStrings( ensureArrayOfStrings( tokens ) ),
											'list_id'
										),
									suggestions: listSuggestions,
									__experimentalExpandOnFocus: true,
							  } )
							: createElement( StyledSelect, {
									...selectControlProps,
									label: __( 'FluentCRM Lists', 'fluent-subs-for-jetformbuilder' ),
									value: selectedLists,
									onChange: ( newValue ) =>
										onChangeSetting(
											uniqueStrings( ensureArrayOfStrings( newValue ) ),
											'list_id'
										),
									options: listOptions,
									multiple: true,
							  } ),
						TokenFieldComponent
							? createElement( TokenFieldComponent, {
									...tokenFieldProps,
									label: __( 'Tags', 'fluent-subs-for-jetformbuilder' ),
									value: selectedTags,
									onChange: ( tokens ) =>
										onChangeSetting(
											uniqueStrings( ensureArrayOfStrings( tokens ) ),
											'tag_id'
										),
									suggestions: tagSuggestions,
									__experimentalExpandOnFocus: true,
							  } )
							: createElement( StyledSelect, {
									...selectControlProps,
									label: __( 'Tags', 'fluent-subs-for-jetformbuilder' ),
									value: selectedTags,
									onChange: ( newValue ) =>
										onChangeSetting(
											uniqueStrings( ensureArrayOfStrings( newValue ) ),
											'tag_id'
										),
									options: tagOptions,
									multiple: true,
							  } )
					),
					createElement( 'hr', {
						className: 'jet-form-builder-separator',
						'aria-hidden': true,
					} )
				),
				/* ---------- Fields map table ---------- */
				createElement( FieldMapControl, {
					fieldsMap,
					onChangeField: updateFieldMap,
					labelFn: label,
				} ),
				/* ---------- Checkboxes ---------- */
				createElement( SwitcherControl, {
					...switcherControlProps,
					label: __( 'Use current user', 'fluent-subs-for-jetformbuilder' ),
					checked: useCurrentUser,
					onChange: ( newValue ) =>
						onChangeSetting( newValue, 'use_current_user' ),
					help: __(
						'Identify the contact by the logged-in user\'s email instead of a form field. When enabled, the email mapping above is ignored.',
						'fluent-subs-for-jetformbuilder'
					),
				} ),
				createElement( SwitcherControl, {
					...switcherControlProps,
					label: __( 'Skip if not in CRM', 'fluent-subs-for-jetformbuilder' ),
					checked: skipNonExisting,
					onChange: ( newValue ) =>
						onChangeSetting( newValue, 'skip_non_existing' ),
					help: __(
						'When enabled, unknown contacts are silently skipped — no new contact is created.',
						'fluent-subs-for-jetformbuilder'
					),
				} ),
				/* ---------- New contact status (full width, only when skip is off) ---------- */
				! skipNonExisting &&
					createElement( StyledSelect, {
						...selectControlProps,
						label: __( 'New contact status', 'fluent-subs-for-jetformbuilder' ),
						value: newContactStatus,
						onChange: ( newValue ) =>
							onChangeSetting( newValue, 'new_contact_status' ),
						options: statusSelectOptions,
						help: __(
							'Status assigned to contacts that are automatically created because they were not found in FluentCRM.',
							'fluent-subs-for-jetformbuilder'
						),
					} )
			);
		},
		{
			category: 'communication',
			docHref:
				'https://github.com/Lonsdale201/fluentcrm-subscriptions-for-jetformbuilder',
		}
	);

	if ( wp?.data?.dispatch ) {
		try {
			wp.data.dispatch( 'jet-forms/actions' ).registerAction( {
				type: 'fluentcrm_add_list_tags',
				label: __( 'FluentCRM Add List & Tags', 'fluent-subs-for-jetformbuilder' ),
				category: 'communication',
				docHref:
					'https://github.com/Lonsdale201/fluentcrm-subscriptions-for-jetformbuilder',
			} );
		} catch ( err ) {
			// Store may not be ready yet.
		}
	}
}( window.wp || false, window.JetFBActions || false, window.JetFluentCrmAddListTags || {}, window.jfb || {} ) );
