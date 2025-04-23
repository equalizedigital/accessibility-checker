/**
 * Test for scanner context exclusions
 * 
 * This test verifies that elements with the selectors in the exclude array
 * are properly excluded from accessibility scans, including the newly added
 * #qm-icon-container selector.
 */
import axe from 'axe-core';

describe('Scanner Context Exclusions', () => {
  beforeEach(() => {
    // Reset the DOM before each test
    document.body.innerHTML = '';
  });

  test('should exclude #qm-icon-container from scan', async () => {
    // Create test HTML with empty buttons (accessibility violation)
    // Some in regular content, others in containers that should be excluded
    document.body.innerHTML = `
      &lt;!-- Control button that should be scanned --&gt;
      <button id="control-button"></button>
      
      &lt;!-- Containers that should be excluded from scan --&gt;
      <div id="qm-icon-container">
        <button id="qm-icon-button"></button>
      </div>
      <div id="wpadminbar">
        <button id="wpadminbar-button"></button>
      </div>
      <div id="query-monitor-main">
        <button id="query-monitor-button"></button>
      </div>
      <div class="edac-panel-container">
        <button id="edac-panel-button"></button>
      </div>
    `;

    // Define context with exclude list matching src/pageScanner/index.js
    const context = { 
      exclude: [ '#wpadminbar', '.edac-panel-container', '#query-monitor-main', '#qm-icon-container' ] 
    };

    // Run axe with button-name rule to detect empty buttons
    const results = await axe.run(context, {
      runOnly: ['button-name']
    });

    // Get all HTML from the violation nodes
    const violationHTML = results.violations
      .flatMap(violation => violation.nodes)
      .map(node => node.html);

    // Control button should appear in violations
    expect(violationHTML.some(html => html.includes('id="control-button"'))).toBe(true);
    
    // Buttons in excluded containers should not appear in violations
    expect(violationHTML.some(html => html.includes('id="qm-icon-button"'))).toBe(false);
    expect(violationHTML.some(html => html.includes('id="wpadminbar-button"'))).toBe(false);
    expect(violationHTML.some(html => html.includes('id="query-monitor-button"'))).toBe(false);
    expect(violationHTML.some(html => html.includes('id="edac-panel-button"'))).toBe(false);
  });
});