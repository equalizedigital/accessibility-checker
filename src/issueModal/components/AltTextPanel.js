import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, Button, Spinner, Notice } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { setPendingRescan } from '../index';

const ALT_RULES = [ 'img_alt_missing', 'img_alt_empty', 'img_alt_invalid' ];
const MAX_RECOMMENDED_LENGTH = 125;

/**
 * Extract the WordPress attachment ID from an img tag's class list.
 *
 * @param {string} markup HTML markup from the issue object.
 * @return {number|null} Attachment ID or null if not found.
 */
const extractAttachmentId = ( markup ) => {
	if ( ! markup ) {
		return null;
	}
	const match = markup.match( /wp-image-(\d+)/ );
	return match ? parseInt( match[ 1 ], 10 ) : null;
};

/**
 * Single suggestion card component.
 *
 * @param {Object}   props            - Component props.
 * @param {Object}   props.suggestion - Suggestion data (alt, focus, explanation).
 * @param {number}   props.index      - Index of this suggestion.
 * @param {boolean}  props.isApplied  - Whether this suggestion has been applied.
 * @param {boolean}  props.isApplying - Whether this suggestion is currently being applied.
 * @param {boolean}  props.disabled   - Whether buttons are disabled.
 * @param {Function} props.onApply    - Apply handler.
 */
const SuggestionCard = ( { suggestion, index, isApplied, isApplying, disabled, onApply } ) => {
	const charCount = suggestion.alt.length;
	const isTooLong = charCount > MAX_RECOMMENDED_LENGTH;

	let buttonLabel = __( 'Apply', 'accessibility-checker' );
	if ( isApplied ) {
		buttonLabel = __( 'Applied ✓', 'accessibility-checker' );
	} else if ( isApplying ) {
		buttonLabel = __( 'Applying…', 'accessibility-checker' );
	}

	return (
		<div className={ `edac-ai-alt-card ${ isApplied ? 'edac-ai-alt-card--applied' : '' }` }>
			<p className="edac-ai-alt-card__text">
				&ldquo;{ suggestion.alt }&rdquo;
			</p>

			<p className="edac-ai-alt-card__meta">
				<span
					className={ `edac-ai-alt-card__chars ${ isTooLong ? 'edac-ai-alt-card__chars--long' : '' }` }
					aria-label={
						isTooLong
							? sprintf(
								/* translators: %d: character count */
								__( '%d characters — exceeds recommended 125', 'accessibility-checker' ),
								charCount,
							)
							: sprintf(
								/* translators: %d: character count */
								__( '%d characters', 'accessibility-checker' ),
								charCount,
							)
					}
				>
					{ charCount }{ ' ' }{ __( 'chars', 'accessibility-checker' ) }
					{ isTooLong && (
						<span className="edac-ai-alt-card__chars-warning" aria-hidden="true">
							{ ' ' }({ __( 'over 125', 'accessibility-checker' ) })
						</span>
					) }
				</span>

				{ suggestion.focus && (
					<span className="edac-ai-alt-card__focus">
						{ ' — ' }{ __( 'Focus:', 'accessibility-checker' ) }{ ' ' }
						<em>{ suggestion.focus }</em>
					</span>
				) }
			</p>

			{ suggestion.explanation && (
				<p className="edac-ai-alt-card__explanation">
					{ suggestion.explanation }
				</p>
			) }

			<Button
				variant={ isApplied ? 'primary' : 'secondary' }
				onClick={ () => onApply( index, suggestion.alt ) }
				disabled={ disabled || isApplied }
				aria-label={ sprintf(
					/* translators: %s: the alt text string */
					__( 'Apply alt text: %s', 'accessibility-checker' ),
					suggestion.alt,
				) }
				className="edac-ai-alt-card__apply-button"
			>
				{ isApplying && <Spinner /> }
				{ buttonLabel }
			</Button>
		</div>
	);
};

/**
 * AltTextPanel Component
 *
 * Renders inside the IssueDetailsModal for image alt text issues, letting
 * users generate AI-powered alt text suggestions and apply them to the attachment.
 *
 * @param {Object}   props          - Component props.
 * @param {Object}   props.rule     - Rule data including slug.
 * @param {Object}   props.issue    - Issue data including HTML object.
 * @param {boolean}  props.isOpen   - Whether the panel body is open.
 * @param {Function} props.onToggle - Panel toggle handler.
 */
const AltTextPanel = ( { rule, issue, isOpen, onToggle } ) => {
	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ suggestions, setSuggestions ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ applyingIndex, setApplyingIndex ] = useState( null );
	const [ appliedIndex, setAppliedIndex ] = useState( null );

	// Only show for image alt rules.
	if ( ! rule?.slug || ! ALT_RULES.includes( rule.slug ) ) {
		return null;
	}

	// Only show when the WordPress AI connector or AI Services plugin is configured.
	if ( ! window.edac_sidebar_app?.aiAltAvailable ) {
		return null;
	}

	const attachmentId = extractAttachmentId( issue?.object );
	if ( ! attachmentId ) {
		return null;
	}

	const handleGenerate = useCallback( async () => {
		setIsGenerating( true );
		setError( null );
		setSuggestions( null );
		setAppliedIndex( null );

		try {
			const response = await apiFetch( {
				path: '/accessibility-checker/v1/generate-alt-text',
				method: 'POST',
				data: { attachment_id: attachmentId, num_suggestions: 3 },
			} );

			if ( response.success ) {
				setSuggestions( response.suggestions );
			} else {
				setError( response.message || __( 'Failed to generate alt text suggestions.', 'accessibility-checker' ) );
			}
		} catch ( err ) {
			setError( err?.message || __( 'An error occurred while contacting the AI service. Please try again.', 'accessibility-checker' ) );
		} finally {
			setIsGenerating( false );
		}
	}, [ attachmentId ] );

	const handleApply = useCallback( async ( index, altText ) => {
		setApplyingIndex( index );
		setError( null );

		try {
			await apiFetch( {
				path: `/wp/v2/media/${ attachmentId }`,
				method: 'POST',
				data: { alt_text: altText },
			} );

			setAppliedIndex( index );
			// Queue a rescan so the issue status updates after the modal closes.
			setPendingRescan( true );
		} catch ( err ) {
			setError( err?.message || __( 'Failed to save the alt text. Please try again.', 'accessibility-checker' ) );
		} finally {
			setApplyingIndex( null );
		}
	}, [ attachmentId ] );

	return (
		<div className="edac-analysis__ai-alt-panel" data-section="ai-alt">
			<Panel>
				<PanelBody
					title={ __( 'Generate AI Alt Text', 'accessibility-checker' ) }
					opened={ isOpen }
					onToggle={ onToggle }
				>
					<div className="edac-analysis__panel-content">
						{ ! suggestions && ! isGenerating && (
							<p className="edac-ai-alt-panel__intro">
								{ __( 'Use your connected AI service to generate accessible alt text suggestions for this image.', 'accessibility-checker' ) }
							</p>
						) }

						{ error && (
							<Notice
								status="error"
								isDismissible
								onRemove={ () => setError( null ) }
							>
								{ error }
							</Notice>
						) }

						{ appliedIndex !== null && (
							<Notice status="success" isDismissible={ false }>
								{ __( 'Alt text applied. This page will be rescanned when you close this dialog.', 'accessibility-checker' ) }
							</Notice>
						) }

						{ ! suggestions && (
							<Button
								variant="secondary"
								onClick={ handleGenerate }
								disabled={ isGenerating }
								className="edac-ai-alt-panel__generate-button"
							>
								{ isGenerating && <Spinner /> }
								{ isGenerating
									? __( 'Generating suggestions…', 'accessibility-checker' )
									: __( 'Generate Suggestions', 'accessibility-checker' ) }
							</Button>
						) }

						{ suggestions && (
							<>
								<div className="edac-ai-alt-panel__suggestions" role="list">
									{ suggestions.map( ( suggestion, index ) => (
										<div key={ index } role="listitem">
											<SuggestionCard
												suggestion={ suggestion }
												index={ index }
												isApplied={ appliedIndex === index }
												isApplying={ applyingIndex === index }
												disabled={ applyingIndex !== null || appliedIndex !== null }
												onApply={ handleApply }
											/>
										</div>
									) ) }
								</div>

								{ appliedIndex === null && (
									<Button
										variant="tertiary"
										onClick={ handleGenerate }
										disabled={ isGenerating }
										className="edac-ai-alt-panel__regenerate-button"
									>
										{ isGenerating && <Spinner /> }
										{ isGenerating
											? __( 'Regenerating…', 'accessibility-checker' )
											: __( 'Regenerate Suggestions', 'accessibility-checker' ) }
									</Button>
								) }
							</>
						) }
					</div>
				</PanelBody>
			</Panel>
		</div>
	);
};

export default AltTextPanel;
