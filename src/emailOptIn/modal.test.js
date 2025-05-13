
/**
 * Tests for the email opt-in modal functionality.
 */
import { jest } from '@jest/globals';
import { initOptInModal } from './modal';
import { createFocusTrap } from 'focus-trap';
import * as modalModule from './modal';

// Mock the focus-trap library
jest.mock('focus-trap', () => ({
  createFocusTrap: jest.fn(() => ({
    activate: jest.fn(),
    deactivate: jest.fn()
  }))
}));

// Alias internal utilities for testing (fallback if not exported)
const bindFocusTrap = modalModule.bindFocusTrap || (() => {});
const onModalClose  = modalModule.onModalClose  || (() => {});

// Mock the global fetch function
global.fetch = jest.fn(() =>
  Promise.resolve({ json: () => Promise.resolve({}) })
);

// Mock jQuery
global.jQuery = jest.fn(() => ({
  one: jest.fn()
}));

// Mock Thickbox globals
global.tb_show   = jest.fn();
global.tb_remove = jest.fn();

beforeEach(() => {
  jest.clearAllMocks();
  jest.useFakeTimers();

  window.onload = null;
  window.edac_email_opt_in_form = {
    ajaxurl: 'https://example.com/wp-admin/admin-ajax.php'
  };

  document.body.innerHTML = `
    <div id="TB_window">
      <button class="tb-close-icon">Close</button>
    </div>
    <div id="edac-opt-in-modal">
      Modal content
    </div>
  `;

  jest.spyOn(window, 'addEventListener');
  jest.spyOn(window, 'removeEventListener');
});

afterEach(() => {
  document.body.innerHTML = '';
  jest.useRealTimers();
});

describe('initOptInModal', () => {
  test('should set window.onload handler', () => {
    initOptInModal();
    expect(typeof window.onload).toBe('function');
  });

  test('should add mousemove and scroll event listeners on window load', () => {
    initOptInModal();
    window.onload();
    expect(window.addEventListener).toHaveBeenCalledTimes(2);
    expect(window.addEventListener).toHaveBeenCalledWith(
      'mousemove',
      expect.any(Function),
      { once: true }
    );
    expect(window.addEventListener).toHaveBeenCalledWith(
      'scroll',
      expect.any(Function),
      { once: true }
    );
  });

  test('mousemove event should trigger the modal', () => {
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    expect(tb_show).toHaveBeenCalledWith(
      'Accessibility Checker',
      '#TB_inline?width=600&inlineId=edac-opt-in-modal',
      null
    );
  });

  test('scroll event should trigger the modal', () => {
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'scroll')[1];
    handler();
    expect(tb_show).toHaveBeenCalledWith(
      'Accessibility Checker',
      '#TB_inline?width=600&inlineId=edac-opt-in-modal',
      null
    );
  });
});

describe('triggerModal function (via event handlers)', () => {
  test('should only run once even if both event handlers fire', () => {
    initOptInModal();
    window.onload();
    const mousemove = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    const scroll    = window.addEventListener.mock.calls
      .find(call => call[0] === 'scroll')[1];
    mousemove();
    scroll();
    expect(tb_show).toHaveBeenCalledTimes(1);
  });

  test('should start polling for the close button to bind focus trap', () => {
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    expect(setInterval).toHaveBeenCalledWith(expect.any(Function), 250);
  });

  test('should stop polling when focus trap is successfully bound', () => {
    document.body.innerHTML = `
      <div id="TB_window">
        <button class="tb-close-icon">Close</button>
      </div>
    `;
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    jest.advanceTimersByTime(250);
    expect(clearInterval).toHaveBeenCalled();
    expect(tb_remove).not.toHaveBeenCalled();
  });

  test('should close modal after 10 failed attempts to bind focus trap', () => {
    document.body.innerHTML = '';
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    jest.advanceTimersByTime(2500);
    expect(clearInterval).toHaveBeenCalled();
    expect(tb_remove).toHaveBeenCalled();
  });
});

describe('bindFocusTrap function', () => {
  test('should return false if TB_window element does not exist', () => {
    document.body.innerHTML = '';
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    jest.advanceTimersByTime(250);
    jest.advanceTimersByTime(2250);
    expect(tb_remove).toHaveBeenCalled();
  });

  test('should return false if close button does not exist', () => {
    document.body.innerHTML = `<div id="TB_window"></div>`;
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    jest.advanceTimersByTime(250);
    jest.advanceTimersByTime(2250);
    expect(tb_remove).toHaveBeenCalled();
  });

  test('should set attributes and create focus trap when elements exist', () => {
    document.body.innerHTML = `
      <div id="TB_window">
        <button class="tb-close-icon">Close</button>
      </div>
    `;
    initOptInModal();
    window.onload();
    const handler = window.addEventListener.mock.calls
      .find(call => call[0] === 'mousemove')[1];
    handler();
    jest.advanceTimersByTime(250);
    expect(createFocusTrap).toHaveBeenCalledWith(
      document.getElementById('TB_window')
    );
    const closeIcon = document.querySelector('.tb-close-icon');
    expect(closeIcon.getAttribute('aria-hidden')).toBe('true');
    expect(jQuery).toHaveBeenCalled();
    expect(jQuery().one).toHaveBeenCalledWith(
      'tb_unload',
      expect.any(Function)
    );
  });
});

describe('onModalClose function', () => {
  test('should deactivate focus trap and make Ajax request', () => {
    const focusTrapMock = { deactivate: jest.fn() };
    if (typeof onModalClose === 'function') {
      onModalClose(focusTrapMock);
      expect(focusTrapMock.deactivate).toHaveBeenCalled();
      expect(fetch).toHaveBeenCalledWith(
        window.edac_email_opt_in_form.ajaxurl +
        '?action=edac_email_opt_in_closed_modal_ajax'
      );
    } else {
      const jOne = jest.fn((_, cb) => cb());
      jQuery.mockReturnValue({ one: jOne });
      document.body.innerHTML = `
        <div id="TB_window">
          <button class="tb-close-icon">Close</button>
        </div>
      `;
      initOptInModal();
      window.onload();
      const handler = window.addEventListener.mock.calls
        .find(call => call[0] === 'mousemove')[1];
      handler();
      jest.advanceTimersByTime(250);
      const trap = { activate: jest.fn(), deactivate: jest.fn() };
      createFocusTrap.mockReturnValue(trap);
      jOne.mock.calls[0][1]();
      expect(trap.deactivate).toHaveBeenCalled();
      expect(fetch).toHaveBeenCalledWith(
        window.edac_email_opt_in_form.ajaxurl +
        '?action=edac_email_opt_in_closed_modal_ajax'
      );
    }
  });
});
