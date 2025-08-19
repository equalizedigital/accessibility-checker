/**
 * Jest tests for AccessibilityCheckerHighlight (frontend highlighter app)
 * Testing Library/Framework: Jest (jsdom)
 *
 * These tests focus on the provided source, covering happy paths, edge cases,
 * and failure conditions. External dependencies are mocked. DOM is simulated
 * via jsdom. We verify public methods and DOM interactions.
 */

/* eslint-disable max-lines */

jest.useFakeTimers();

jest.mock('@floating-ui/dom', () => ({
  computePosition: jest.fn(() => Promise.resolve({ x: 10, y: 20, middlewareData: {}, placement: 'top-start' })),
  autoUpdate: jest.fn((_refEl, _floatingEl, update) => {
    // Immediately call update once to simulate positioning
    if (typeof update === 'function') {
      update();
    }
    // Return cleanup function
    return jest.fn();
  }),
}));

const mockTrap = () => ({
  activate: jest.fn(),
  deactivate: jest.fn(),
  pause: jest.fn(),
  unpause: jest.fn(),
});
jest.mock('focus-trap', () => ({
  createFocusTrap: jest.fn(() => mockTrap()),
}));

jest.mock('tabbable', () => ({
  isFocusable: jest.fn(() => true),
}));

// WordPress i18n mocks
jest.mock('@wordpress/i18n', () => ({
  __: (s) => s,
  _n: (singular, plural, count) => (count === 1 ? singular : plural),
}));

// Module stubs used by the class
jest.mock('../../../src/common/saveFixSettingsRest', () => ({
  saveFixSettings: jest.fn(),
}), { virtual: true });

jest.mock('../../../src/frontendHighlighterApp/fixesModal', () => ({
  fillFixesModal: jest.fn(),
  fixSettingsModalInit: jest.fn(),
  openFixesModal: jest.fn(),
}), { virtual: true });

jest.mock('../../../src/common/helpers', () => ({
  hashString: jest.fn((s) => `hash_${(s || '').length}`),
}), { virtual: true });

// Dynamically import the module under test by searching known locations.
// Adjust this import if project structure differs.
let AccessibilityCheckerHighlight;

beforeAll(async () => {
  // Provide globals expected by the component
  global.edacFrontendHighlighterApp = {
    ajaxurl: '/ajax',
    postID: '123',
    nonce: 'abc',
    widgetPosition: 'right',
    loggedIn: true,
    userCanEdit: true,
    userCanFix: true,
    appCssUrl: '/assets/app.css',
    edacUrl: 'https://example.com',
    restNonce: 'rest-123',
    scannerBundleUrl: '/assets/scanner.js',
  };

  // Build a minimal DOM head/body that the class expects
  document.head.innerHTML = '<meta charset="utf-8"><title>Test</title>';
  document.body.innerHTML = '<div id="root"></div>';

  // Attempt to import from common paths
  let imported = null;
  try {
    imported = await import('../../../src/frontendHighlighterApp/index.js');
  } catch (e1) {
    try {
      imported = await import('../../../assets/frontendHighlighterApp/index.js');
    } catch (e2) {
      // Fallback: allow tests that only rely on public methods mocked if needed
      throw new Error('Unable to locate the AccessibilityCheckerHighlight module. Adjust import path in tests/jest/frontendHighlighterApp/index.test.js');
    }
  }
  AccessibilityCheckerHighlight = imported.default || imported.AccessibilityCheckerHighlight;
});

afterEach(() => {
  // Reset DOM between tests to avoid cross-test pollution
  document.body.className = '';
  document.body.innerHTML = '';
  document.head.innerHTML = '';
  // Rehydrate basic DOM
  document.head.innerHTML = '<meta charset="utf-8"><title>Test</title>';
  document.body.innerHTML = '<div id="root"></div>';

  // Reset any timers and mocks
  jest.runOnlyPendingTimers();
  jest.useRealTimers();
  jest.useFakeTimers();
  jest.clearAllMocks();
});

describe('AccessibilityCheckerHighlight - constructor and init', () => {
  test('creates panel markup and wires listeners; panel toggles open and close', () => {
    const instance = new AccessibilityCheckerHighlight();

    const panel = document.getElementById('edac-highlight-panel');
    expect(panel).toBeTruthy();

    const toggle = document.getElementById('edac-highlight-panel-toggle');
    const close = document.getElementById('edac-highlight-panel-controls-close');

    // Initially, controls hidden until open
    const controls = document.getElementById('edac-highlight-panel-controls');
    expect(controls.style.display).toBe(''); // hidden by default before open

    // Open
    toggle.click();

    // highlightAjax will run; mock its XHR path so it does not throw (we will simulate 404 -> reject which is handled)
    const summary = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(panel.classList.contains('edac-highlight-panel-visible')).toBe(true);
    expect(controls.style.display).toBe('block');
    expect(toggle.style.display).toBe('none');

    // Close
    close.click();
    expect(panel.classList.contains('edac-highlight-panel-visible')).toBe(false);
    expect(controls.style.display).toBe('none');
    const description = document.getElementById('edac-highlight-panel-description');
    expect(description.style.display).toBe('none');
    expect(toggle.style.display).toBe('block');
  });

  test('URL parameter edac opens panel; edac_landmark triggers highlightLandmark', () => {
    // Set window.location.search
    const orig = window.location;
    delete window.location;
    // Provide edac param
    window.location = new URL('https://example.com/?edac=issue-1');
    const instance = new AccessibilityCheckerHighlight();
    // Since highlightAjax is async, just verify open was triggered
    expect(document.getElementById('edac-highlight-panel').classList.contains('edac-highlight-panel-visible')).toBe(true);

    // Now simulate landmark param
    document.body.innerHTML = '<main id="m">Hello</main>';
    window.location = new URL('https://example.com/?edac_landmark=' + btoa('main#m'));
    const instance2 = new AccessibilityCheckerHighlight();
    // Should attempt to highlight. Landmark label can be added if element found
    expect(document.querySelector('main').classList.contains('edac-highlight-element-selected')).toBe(true);

    // restore
    window.location = orig;
  });
});

describe('highlightAjax - success, filtering, kickoffScan (-3), error', () => {
  function installMockXHR(sequence) {
    // sequence: array of { status, responseText }
    let callIndex = 0;
    class MockXHR {
      constructor() {
        this.status = 0;
        this.statusText = '';
        this.responseText = '';
        this.onload = null;
        this.onerror = null;
      }
      open(_method, _url) {}
      send() {
        const resp = sequence[Math.min(callIndex, sequence.length - 1)];
        callIndex++;
        if (resp.error) {
          if (this.onerror) this.onerror();
          return;
        }
        setTimeout(() => {
          this.status = resp.status;
          this.statusText = resp.statusText || '';
          this.responseText = resp.responseText || '';
          if (this.onload) this.onload();
        }, 0);
        jest.runOnlyPendingTimers();
      }
    }
    global.XMLHttpRequest = MockXHR;
  }

  test('resolves with filtered issues when showIgnored=false (default)', async () => {
    const payload = {
      issues: [
        { id: 'a', rule_type: 'error', object: '<div>1</div>' },
        { id: 'b', rule_type: 'ignored', object: '<span>2</span>' },
        { id: 'c', rule_type: null, object: '<i>3</i>' }, // should be filtered due to null
      ],
      fixes: { slug: {} },
    };
    installMockXHR([
      { status: 200, responseText: JSON.stringify({ success: true, data: JSON.stringify(payload) }) },
    ]);

    // Put a matching element in DOM so findElement can match
    document.body.innerHTML = '<div>1</div><span>2</span><i>3</i>';

    const instance = new AccessibilityCheckerHighlight();
    const result = await instance.highlightAjax();
    expect(Array.isArray(result.issues)).toBe(true);
    // 'ignored' and null rule_type filtered unless its id matches url param
    expect(result.issues.map(i => i.id)).toEqual(['a']);
    expect(result.fixes).toEqual(payload.fixes);
  });

  test('resolves with all issues when showIgnored=true', async () => {
    const payload = {
      issues: [
        { id: 'a', rule_type: 'error', object: '<div>1</div>' },
        { id: 'b', rule_type: 'ignored', object: '<span>2</span>' },
      ],
      fixes: { slug: {} },
    };
    installMockXHR([
      { status: 200, responseText: JSON.stringify({ success: true, data: JSON.stringify(payload) }) },
    ]);

    document.body.innerHTML = '<div>1</div><span>2</span>';

    const instance = new AccessibilityCheckerHighlight({ showIgnored: true });
    const result = await instance.highlightAjax();
    expect(result.issues.map(i => i.id)).toEqual(['a', 'b']);
  });

  test('on success=false and code -3 triggers kickoffScan once and retries highlightAjax', async () => {
    // First call returns -3 code, second returns success
    const dataMinus3 = [{ code: -3 }];
    const payloadAfter = {
      issues: [{ id: 'a', rule_type: 'error', object: '<div>1</div>' }],
      fixes: {},
    };
    installMockXHR([
      { status: 200, responseText: JSON.stringify({ success: false, data: dataMinus3 }) },
      { status: 200, responseText: JSON.stringify({ success: true, data: JSON.stringify(payloadAfter) }) },
    ]);

    // Spy on kickoffScan
    const instance = new AccessibilityCheckerHighlight();
    const spy = jest.spyOn(instance, 'kickoffScan').mockImplementation(() => {});
    const p = instance.highlightAjax();
    // A 5s timer is scheduled for retry; fast-forward
    jest.advanceTimersByTime(5000);
    const result = await p;
    expect(spy).toHaveBeenCalledTimes(1);
    expect(result.issues.map(i => i.id)).toEqual(['a']);
  });

  test('rejects on non-200 xhr status', async () => {
    installMockXHR([{ status: 500, statusText: 'Server Error', responseText: '' }]);
    const instance = new AccessibilityCheckerHighlight();
    await expect(instance.highlightAjax()).rejects.toMatchObject({ status: 500, statusText: 'Server Error' });
  });

  test('rejects on xhr network error', async () => {
    installMockXHR([{ error: true }]);
    const instance = new AccessibilityCheckerHighlight();
    await expect(instance.highlightAjax()).rejects.toMatchObject({ status: undefined, statusText: undefined });
  });
});

describe('findElement and addTooltip integration', () => {
  test('finds matching element by outerHTML (whitespace-insensitive), adds tooltip with handlers and positioning', async () => {
    // Element under test
    document.body.innerHTML = '<div id="target"><span>content</span></div>';
    const instance = new AccessibilityCheckerHighlight();
    instance.issues = [{ id: 'id1' }];

    const value = { id: 'id1', rule_type: 'error', rule_title: 'Title', object: '   <div id="target"> <span>content</span>  </div>  ' };
    const element = instance.findElement(value, 0);
    expect(element).toBeTruthy();

    const tooltip = document.querySelector('.edac-highlight-btn');
    expect(tooltip).toBeTruthy();
    expect(tooltip.getAttribute('aria-label')).toContain('Open details for');
    expect(tooltip.dataset.id).toBe('id1');
    // computePosition invoked
    const { computePosition, autoUpdate } = require('@floating-ui/dom');
    expect(computePosition).toHaveBeenCalled();

    // Clicking the tooltip calls showIssue with matching id
    const spy = jest.spyOn(instance, 'showIssue').mockImplementation(() => {});
    tooltip.click();
    expect(spy).toHaveBeenCalledWith('id1');
  });

  test('returns null when no element matches', () => {
    document.body.innerHTML = '<div id="other"></div>';
    const instance = new AccessibilityCheckerHighlight();
    instance.issues = [{ id: 'x' }];
    const v = { id: 'x', rule_type: 'warning', rule_title: 'x', object: '<section>nope</section>' };
    expect(instance.findElement(v, 0)).toBeNull();
  });
});

describe('showIssue behavior including visibility and focusability', () => {
  test('selects tooltip and element, scrolls into view, sets status accordingly', () => {
    document.body.innerHTML = '<div id="target">x</div>';
    const instance = new AccessibilityCheckerHighlight();
    instance.issues = [{
      id: 'id1',
      element: document.getElementById('target'),
      tooltip: (() => {
        const b = document.createElement('button');
        b.className = 'edac-highlight-btn';
        document.body.appendChild(b);
        return b;
      })(),
    }];

    // Mock tabbable.isFocusable
    const tabbable = require('tabbable');
    tabbable.isFocusable.mockReturnValue(true);

    // Provide is-visible dimensions
    Object.defineProperty(instance.issues[0].element, 'offsetWidth', { value: 50 });
    Object.defineProperty(instance.issues[0].element, 'offsetHeight', { value: 50 });

    // Spy descriptionOpen
    const descSpy = jest.spyOn(instance, 'descriptionOpen').mockImplementation(() => {});

    instance.showIssue('id1');

    expect(instance.currentButtonIndex).toBe(0);
    expect(instance.issues[0].tooltip.classList.contains('edac-highlight-btn-selected')).toBe(true);
    expect(instance.issues[0].element.classList.contains('edac-highlight-element-selected')).toBe(true);
    expect(instance.currentIssueStatus).toBeNull();
    expect(descSpy).toHaveBeenCalledWith('id1');
  });

  test('sets not focusable status when isFocusable returns false', () => {
    document.body.innerHTML = '<div id="target">x</div>';
    const instance = new AccessibilityCheckerHighlight();
    const el = document.getElementById('target');
    const t = document.createElement('button');
    document.body.appendChild(t);
    instance.issues = [{ id: 'id2', element: el, tooltip: t }];

    // Mock tabbable.isFocusable -> false
    const tabbable = require('tabbable');
    tabbable.isFocusable.mockReturnValue(false);

    instance.showIssue('id2');
    expect(instance.currentIssueStatus).toMatch(/not focusable/i);
  });

  test('sets not visible status when element not visible by fallback', () => {
    document.body.innerHTML = '<div id="target" style="display:none"></div>';
    const instance = new AccessibilityCheckerHighlight();
    const el = document.getElementById('target');
    const t = document.createElement('button');
    document.body.appendChild(t);
    instance.issues = [{ id: 'id3', element: el, tooltip: t }];

    // Make fallback visibility return false
    Object.defineProperty(el, 'offsetWidth', { value: 0 });
    Object.defineProperty(el, 'offsetHeight', { value: 0 });
    Object.defineProperty(el, 'getClientRects', { value: () => ({ length: 0 }) });

    instance.showIssue('id3');
    expect(instance.currentIssueStatus).toMatch(/not visible/i);
  });

  test('sets element not found status when missing', () => {
    const instance = new AccessibilityCheckerHighlight();
    instance.issues = [{ id: 'foo' }];
    instance.showIssue('foo');
    expect(instance.currentIssueStatus).toMatch(/not found/i);
  });
});

describe('showIssueCount counts and renders summary, also enables nav buttons', () => {
  test('renders "No issues detected." when none', () => {
    const instance = new AccessibilityCheckerHighlight();
    // Force panel open to ensure summary element exists
    document.getElementById('edac-highlight-panel-toggle').click();
    instance.issues = [];
    instance.showIssueCount();
    const summary = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(summary.textContent).toBe('No issues detected.');
    expect(instance.nextButton.disabled).toBe(true);
    expect(instance.previousButton.disabled).toBe(true);
  });

  test('renders counts for error, warning, ignored; enables next/prev', () => {
    const instance = new AccessibilityCheckerHighlight();
    document.getElementById('edac-highlight-panel-toggle').click();
    instance.issues = [
      { rule_type: 'error', ignored: 0 },
      { rule_type: 'warning', ignored: 0 },
      { rule_type: 'error', ignored: 1 },
      { rule_type: 'warning', ignored: 1 },
    ];
    instance.showIssueCount();
    const summary = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(summary.textContent).toMatch(/2 errors/);
    expect(summary.textContent).toMatch(/2 warnings/);
    expect(summary.textContent).toMatch(/2 ignored issues/);
    expect(instance.nextButton.disabled).toBe(false);
    expect(instance.previousButton.disabled).toBe(false);
  });
});

describe('codeToggle toggles display and aria-expanded', () => {
  test('toggles container visibility', () => {
    const instance = new AccessibilityCheckerHighlight();
    // Seed code container + button
    const container = document.createElement('div');
    container.className = 'edac-highlight-panel-description-code';
    const code = document.createElement('code');
    container.appendChild(code);
    const button = document.createElement('button');
    button.className = 'edac-highlight-panel-description-code-button';
    document.body.append(container, button);
    instance.codeContainer = container;
    instance.codeButton = button;

    instance.codeToggle();
    expect(container.style.display).toBe('block');
    expect(button.getAttribute('aria-expanded')).toBe('true');

    instance.codeToggle();
    expect(container.style.display).toBe('none');
    expect(button.getAttribute('aria-expanded')).toBe('false');
  });
});

describe('get_url_parameter parses window.location.search', () => {
  test('returns decoded value when present', () => {
    const orig = window.location;
    delete window.location;
    window.location = new URL('https://example.com/?edac=abc%20123&other=1');
    const instance = new AccessibilityCheckerHighlight();
    expect(instance.get_url_parameter('edac')).toBe('abc 123');
    window.location = orig;
  });

  test('returns true when present without value, false when absent', () => {
    const orig = window.location;
    delete window.location;
    window.location = new URL('https://example.com/?flag&other=1');
    const instance = new AccessibilityCheckerHighlight();
    expect(instance.get_url_parameter('flag')).toBe(true);
    expect(instance.get_url_parameter('missing')).toBe(false);
    window.location = orig;
  });
});

describe('highlightLandmark and removeLandmarkLabels', () => {
  test('labels and highlights a found landmark; cleanup removes labels/classes', () => {
    document.body.innerHTML = '<main id="mainArea">X</main>';
    const instance = new AccessibilityCheckerHighlight();
    const selector = btoa('main#mainArea');

    instance.highlightLandmark(selector);

    const main = document.querySelector('main#mainArea');
    expect(main.classList.contains('edac-landmark-highlight')).toBe(true);
    expect(document.querySelector('.edac-landmark-label')).toBeTruthy();

    instance.removeLandmarkLabels();
    expect(document.querySelector('.edac-landmark-label')).toBeFalsy();
    expect(main.classList.contains('edac-landmark-highlight')).toBe(false);
  });

  test('getLandmarkType falls back via role, semantic tags, and defaults', () => {
    const instance = new AccessibilityCheckerHighlight();
    const e = document.createElement('div');
    e.setAttribute('role', 'navigation');
    expect(instance.getLandmarkType(e)).toBe('Navigation');

    const header = document.createElement('header');
    expect(instance.getLandmarkType(header)).toBe('Header');

    const section = document.createElement('section');
    expect(instance.getLandmarkType(section)).toMatch(/Section|Region/);

    const x = document.createElement('div');
    expect(instance.getLandmarkType(x)).toBe('Landmark');
  });
});

describe('disableStyles and enableStyles', () => {
  test('disables styles by removing <link rel="stylesheet"> and style attributes, then re-enables', () => {
    // Seed styles and inline style elements
    const link1 = document.createElement('link');
    link1.rel = 'stylesheet';
    link1.href = '/a.css';
    const link2 = document.createElement('link');
    link2.rel = 'stylesheet';
    link2.id = 'dashicons-css'; // excluded from removal filter
    link2.href = '/dashicons.css';
    document.head.append(link1, link2);

    const styled = document.createElement('div');
    styled.setAttribute('style', 'color:red');
    document.body.appendChild(styled);

    const instance = new AccessibilityCheckerHighlight();
    // Make sure app CSS is considered present to avoid injecting a new link
    const appCss = document.createElement('link');
    appCss.rel = 'stylesheet';
    appCss.id = 'edac-app-css';
    document.head.appendChild(appCss);

    instance.disableStyles();
    expect(document.body.classList.contains('edac-app-disable-styles')).toBe(true);
    expect(instance.stylesDisabled).toBe(true);
    expect(instance.disableStylesButton.textContent).toBe('Enable Styles');

    // All non-exempt styles removed
    expect(Array.from(document.head.querySelectorAll('link[rel="stylesheet"]')).some(l => l.href.endsWith('/a.css'))).toBe(false);

    instance.enableStyles();
    expect(document.body.classList.contains('edac-app-disable-styles')).toBe(false);
    expect(instance.stylesDisabled).toBe(false);
    expect(instance.disableStylesButton.textContent).toBe('Disable Styles');
  });
});

describe('kickoffScan flow and runAccessibilityScanAndSave', () => {
  test('when window.runAccessibilityScan missing -> showScanError called', () => {
    const instance = new AccessibilityCheckerHighlight();
    const errSpy = jest.spyOn(instance, 'showScanError').mockImplementation(() => {});
    instance._runScanOrShowError({ elementCount: 0, contentLength: 0 });
    expect(errSpy).toHaveBeenCalledWith('Scanner function not found.');
  });

  test('runAccessibilityScanAndSave handles missing postId/nonce', async () => {
    const instance = new AccessibilityCheckerHighlight();
    const errSpy = jest.spyOn(instance, 'showScanError').mockImplementation(() => {});
    global.window.runAccessibilityScan = jest.fn(() => Promise.resolve({ violations: [{}] }));
    // Remove postID and nonce to trigger error
    global.edacFrontendHighlighterApp.postID = undefined;
    global.edacFrontendHighlighterApp.restNonce = undefined;
    await instance.runAccessibilityScanAndSave({ elementCount: 1, contentLength: 2 });
    expect(errSpy).toHaveBeenCalledWith('Missing postId or nonce.');
  });

  test('runAccessibilityScanAndSave handles empty violations', async () => {
    const instance = new AccessibilityCheckerHighlight();
    const errSpy = jest.spyOn(instance, 'showScanError').mockImplementation(() => {});
    global.edacFrontendHighlighterApp.postID = '123';
    global.edacFrontendHighlighterApp.restNonce = 'rest-123';
    global.window.runAccessibilityScan = jest.fn(() => Promise.resolve({ violations: [] }));
    await instance.runAccessibilityScanAndSave({ elementCount: 1, contentLength: 2 });
    expect(errSpy).toHaveBeenCalledWith('No violations found, skipping save.');
  });

  test('saveScanResults success and failure branches', async () => {
    const instance = new AccessibilityCheckerHighlight();
    global.fetch = jest.fn()
      // success response
      .mockResolvedValueOnce({
        json: () => Promise.resolve({ success: true }),
        ok: true,
      })
      // failure response (success false)
      .mockResolvedValueOnce({
        json: () => Promise.resolve({ success: false }),
        ok: true,
      })
      // network error for catch
      .mockRejectedValueOnce(new Error('net'));

    const errSpy = jest.spyOn(instance, 'showScanError').mockImplementation(() => {});

    await instance.saveScanResults('1', 'nonce', [{ id: 1 }], { elementCount: 1, contentLength: 2 });
    expect(errSpy).not.toHaveBeenCalled();

    await instance.saveScanResults('1', 'nonce', [{ id: 1 }], { elementCount: 1, contentLength: 2 });
    expect(errSpy).toHaveBeenCalledWith('Saving failed.');

    await instance.saveScanResults('1', 'nonce', [{ id: 1 }], { elementCount: 1, contentLength: 2 });
    expect(errSpy).toHaveBeenCalledWith('Error saving scan results.');
  });
});

describe('rescanPage prevents concurrent rescans and triggers panelOpen after delay', () => {
  test('respects _isRescanning flag and calls kickoffScan; then panelOpen after 5s', () => {
    const instance = new AccessibilityCheckerHighlight();
    const kickoffSpy = jest.spyOn(instance, 'kickoffScan').mockImplementation(() => {});
    const openSpy = jest.spyOn(instance, 'panelOpen').mockImplementation(() => {});
    instance._isRescanning = false;

    instance.rescanPage();
    expect(kickoffSpy).toHaveBeenCalled();

    // Second call should be ignored while rescanning
    instance.rescanPage();
    expect(kickoffSpy).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(5000);
    expect(openSpy).toHaveBeenCalled();
    expect(instance._isRescanning).toBe(false);
  });
});

describe('clearIssues fetch flow', () => {
  test('shows error message when required params missing', async () => {
    const instance = new AccessibilityCheckerHighlight();
    // Force panel open so summary is present
    document.getElementById('edac-highlight-panel-toggle').click();
    // Remove required params
    global.edacFrontendHighlighterApp.edacUrl = undefined;
    global.edacFrontendHighlighterApp.postID = undefined;

    // Spy on confirm to return true
    global.confirm = jest.fn(() => true);

    await instance.clearIssues();
    const summary = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(summary.textContent).toMatch(/Missing required parameters/);
    expect(summary.classList.contains('edac-error')).toBe(true);
  });

  test('clears issues on success; shows failure on non-ok and network error', async () => {
    const instance = new AccessibilityCheckerHighlight();
    document.getElementById('edac-highlight-panel-toggle').click();
    global.edacFrontendHighlighterApp.edacUrl = 'https://example.com';
    global.edacFrontendHighlighterApp.postID = '999';
    global.confirm = jest.fn(() => true);

    // Seed issues and buttons
    instance.issues = [{ id: 'x' }, { id: 'y' }];
    // Provide clearIssuesButton if not present (may be omitted if userCanEdit is false)
    if (!instance.clearIssuesButton) {
      const btn = document.createElement('button');
      btn.id = 'edac-highlight-clear-issues';
      document.body.appendChild(btn);
      instance.clearIssuesButton = btn;
    }

    const okResponse = {
      ok: true,
      json: () => Promise.resolve({}),
    };
    const notOkResponse = {
      ok: false,
      json: () => Promise.resolve({}),
    };

    global.fetch = jest.fn()
      .mockResolvedValueOnce(okResponse)
      .mockResolvedValueOnce(notOkResponse)
      .mockRejectedValueOnce(new Error('net'));

    await instance.clearIssues();
    const summary1 = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(instance.issues).toEqual([]);
    expect(summary1.textContent).toMatch(/Issues cleared successfully/);

    await instance.clearIssues();
    const summary2 = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(summary2.textContent).toMatch(/Failed to clear issues/);

    await instance.clearIssues();
    const summary3 = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(summary3.textContent).toMatch(/An error occurred while clearing issues/);
  });
});

describe('showScanError writes message to summary and adds class', () => {
  test('writes error message', () => {
    const instance = new AccessibilityCheckerHighlight();
    document.getElementById('edac-highlight-panel-toggle').click();
    instance.showScanError('Boom');
    const summary = document.querySelector('.edac-highlight-panel-controls-summary');
    expect(summary.textContent).toBe('Boom');
    expect(summary.classList.contains('edac-error')).toBe(true);
  });
});