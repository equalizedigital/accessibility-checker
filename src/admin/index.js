
(function ($) {
  "use strict";

  $(function () {

    // Accessibility Statement disable
    $("input[type=checkbox][name=edac_add_footer_accessibility_statement]").on('change', function () {
      if (this.checked) {
        $("input[type=checkbox][name=edac_include_accessibility_statement_link]").prop('disabled', false);
      } else {
        $("input[type=checkbox][name=edac_include_accessibility_statement_link]").prop('disabled', true);
        $("input[type=checkbox][name=edac_include_accessibility_statement_link]").prop('checked', false);
      }
      //
    });

    // Show Simplified Summary code on options page
    if ($("input[type=radio][name=edac_simplified_summary_position]:checked").val() == "none") {
      $("#ac-simplified-summary-option-code").show();
    }
    $("input[type=radio][name=edac_simplified_summary_position]").on('load', function () {
      if (this.value == "none") {
        $("#ac-simplified-summary-option-code").show();
      } else {
        $("#ac-simplified-summary-option-code").hide();
      }
    });

  });


  $(window).on('load', function () {

    /**
     * Tabs
     */

    // Refresh data on summary and readability tabs
    const refresh_summary_and_readability = () => {

      edac_summary_ajax(
        () => {
          edac_readability_ajax();
          $(".edac-panel").removeClass("edac-panel-loading");
        }
      );

    };

    $(".edac-tab").click(function (e) {
      e.preventDefault();
      var id = $("a", this).attr("href");

      $(".edac-panel").hide();
      $(".edac-panel").removeClass("active");
      $(".edac-tab a").removeClass("active").attr("aria-current", false);
      $(id).show();
      $(id).addClass("active");
      $("a", this).addClass("active").attr("aria-current", true);
    });

    // Details Tab on click Ajax
    $(".edac-tab-details").click(function (e) {
      edac_details_ajax();
    });

    // Summary Tab on click Ajax
    $(".edac-tab-summary").click(function (e) {
      refresh_summary_and_readability();
    });

    /**
     * Ajax Summary
     */
    function edac_summary_ajax(callback = null) {

      let post_id = edac_script_vars.postID;

      if (post_id == null) {
        return;
      }

      $.ajax({

        url: ajaxurl,
        method: 'GET',
        data: { action: 'edac_summary_ajax', post_id: post_id, nonce: edac_script_vars.nonce }

      }).done(function (response) {

        if (true === response.success) {

          let response_json = $.parseJSON(response.data);

          /*
          if(response_json.password_protected && edac_gutenberg_active()){
            wp.data.dispatch('core/notices').createInfoNotice(
              response_json.password_protected, 
              {
                id: 'edac-password-protected-error',
                type: 'default', //default, or snackbar
                speak: true,
                __unstableHTML: true,
              },
            );
          }
          */

          $(".edac-summary").html(response_json.content);

          if (typeof callback === 'function') {
            callback();
          }

        } else {

          console.log(response);

        }

      });

    }

    /**
     * Ajax Details
     */
    function edac_details_ajax() {

      let post_id = edac_script_vars.postID;

      if (post_id == null) {
        return;
      }

      $.ajax({

        url: ajaxurl,
        method: 'GET',
        data: { action: 'edac_details_ajax', post_id: post_id, nonce: edac_script_vars.nonce }

      }).done(function (response) {

        if (true === response.success) {

          let response_json = $.parseJSON(response.data);

          $(".edac-details").html(response_json);

          // Rule on click
          $(".edac-details-rule-title").click(function (e) {
            //$('.edac-details-rule-records').slideUp();
            if ($(this).hasClass("active")) {
              $(this).next().slideUp();
              $(this).removeClass("active");
            } else {
              $(this).next().slideDown();
              $(this).addClass("active");
            }
          });

          // Title arrow button on click
          $(".edac-details-rule-title-arrow").click(function (e) {
            e.preventDefault();
            if ($(this).attr("aria-expanded") === "true") {
              $(this).attr("aria-expanded", "false");
            } else {
              $(this).attr("aria-expanded", "true");
            }
          });

          // Ignore on click
          $(".edac-details-rule-records-record-actions-ignore").click(function (e) {
            e.preventDefault();
            $(this).parent().next(".edac-details-rule-records-record-ignore").slideToggle();
            if ($(this).attr("aria-expanded") === "true") {
              $(this).attr("aria-expanded", "false");
            } else {
              $(this).attr("aria-expanded", "true");
            }
          });

          // Ignore submit on click
          ignore_submit();

        } else {

          console.log(response);

        }

      });

    }

    /**
     * Ajax Readability
     */
    function edac_readability_ajax() {

      let post_id = edac_script_vars.postID;

      if (post_id == null) {
        return;
      }

      $.ajax({

        url: ajaxurl,
        method: 'GET',
        data: { action: 'edac_readability_ajax', post_id: post_id, nonce: edac_script_vars.nonce }

      }).done(function (response) {

        if (true === response.success) {

          let response_json = $.parseJSON(response.data);

          $(".edac-readability").html(response_json);

          // Simplified Summary on click
          $(".edac-readability-simplified-summary").submit(function (event) {
            event.preventDefault();

            //var post_id = wp.data.select("core/editor").getCurrentPostId();
            let summary = $("#edac-readability-text").val();

            $.ajax({

              url: ajaxurl,
              method: 'GET',
              data: { action: 'edac_update_simplified_summary', post_id: post_id, summary: summary, nonce: edac_script_vars.nonce }

            }).done(function (response) {

              if (true === response.success) {

                let response_json = $.parseJSON(response.data);

                refresh_summary_and_readability();

              } else {

                console.log(response);

              }

            });

          });

        } else {

          console.log(response);

        }

      });

    }

    /**
     * On Post Save Gutenberg
     */
    if (edac_gutenberg_active()) {
      var editPost = wp.data.select("core/edit-post"),
        lastIsSaving = false;

      wp.data.subscribe(function () {
        var isSaving = editPost.isSavingMetaBoxes();
        if (isSaving) {
          $(".edac-panel").addClass("edac-panel-loading");
        }
        if (isSaving !== lastIsSaving && !isSaving) {
          lastIsSaving = isSaving;


          // reset to first meta box tab
          $(".edac-panel").hide();
          $(".edac-panel").removeClass("active");
          $(".edac-tab a").removeClass("active");
          $("#edac-summary").show();
          $("#edac-summary").addClass("active");
          $(".edac-tab:first-child a").addClass("active");

          edac_details_ajax();
          refresh_summary_and_readability();

        }
        lastIsSaving = isSaving;
      });
    }

    /**
     * Ignore Submit on click
     */
    function ignore_submit() {

      $(".edac-details-rule-records-record-ignore-submit").click(function (e) {
        e.preventDefault();

        let ids = [$(this).attr("data-id")];
        let ignore_action = $(this).attr("data-action");
        let ignore_type = $(this).attr("data-type");
        let comment = $(
          ".edac-details-rule-records-record-ignore-comment",
          $(this).parent()
        ).val();

        $.ajax({

          url: ajaxurl,
          method: 'GET',
          data: {
            action: 'edac_insert_ignore_data',
            ids: ids,
            comment: comment,
            ignore_action: ignore_action,
            ignore_type: ignore_type,
            nonce: edac_script_vars.nonce
          }

        }).done(function (response) {

          if (true === response.success) {

            let data = $.parseJSON(response.data);

            let record = "#edac-details-rule-records-record-" + data["ids"][0];
            let ignore_action = data["action"] == "enable" ? "disable" : "enable";
            let comment_disabled = data["action"] == "enable" ? true : false;
            let actions_ignore_label = data["action"] == "enable" ? "Ignored" : "Ignore";
            let ignore_submit_label = data["action"] == "enable" ? "Stop Ignoring" : "Ignore This " + data['type'];
            let username = data["user"] ? "<strong>Username:</strong> " + data["user"] : "";
            let date = data["date"] ? "<strong>Date:</strong> " + data["date"] : "";

            $(record + " .edac-details-rule-records-record-ignore-submit").attr("data-action", ignore_action);
            $(record + " .edac-details-rule-records-record-ignore-comment").attr("disabled", comment_disabled);
            if (data["action"] != "enable") {
              $(record + " .edac-details-rule-records-record-ignore-comment").val("");
            }
            $(record + " .edac-details-rule-records-record-actions-ignore").toggleClass("active");
            $(".edac-details-rule-records-record-actions-ignore[data-id='" + ids[0] + "']").toggleClass("active"); // pro
            $(record + " .edac-details-rule-records-record-actions-ignore-label").html(actions_ignore_label);
            $(".edac-details-rule-records-record-actions-ignore[data-id='" + ids[0] + "'] .edac-details-rule-records-record-actions-ignore-label").html(actions_ignore_label); // pro
            $(record + " .edac-details-rule-records-record-ignore-submit-label").html(ignore_submit_label);
            $(record + " .edac-details-rule-records-record-ignore-info-user").html(username);
            $(record + " .edac-details-rule-records-record-ignore-info-date").html(date);

            // Update rule count
            let rule = $(record).parents(".edac-details-rule");
            let count = parseInt($(".edac-details-rule-count", rule).html());
            if (data["action"] == "enable") {
              count--;
            } else if (data["action"] == "disable") {
              count++;
            }
            if (count == 0) {
              $(".edac-details-rule-count", rule).removeClass("active");
            } else {
              $(".edac-details-rule-count", rule).addClass("active");
            }
            count.toString();
            $(".edac-details-rule-count", rule).html(count);

            // Update ignore rule count
            var count_ignore = parseInt($(".edac-details-rule-count-ignore", rule).html());
            //console.log(count_ignore);
            if (data["action"] == "enable") {
              count_ignore++;
            } else if (data["action"] == "disable") {
              count_ignore--;
            }
            if (count_ignore == 0) {
              $(".edac-details-rule-count-ignore", rule).hide();
            } else {
              $(".edac-details-rule-count-ignore", rule).show();
            }
            count_ignore.toString();
            $(".edac-details-rule-count-ignore", rule).html(count_ignore + ' Ignored Items');

            // refresh page on ignore or unignore in pro
            if ($('body').hasClass('accessibility-checker_page_accessibility_checker_issues') || $('body').hasClass('accessibility-checker_page_accessibility_checker_ignored')) {
              location.reload(true);
            }

          } else {

            console.log(response);

          }

        });

      });
    }

    /**
     * Check if Gutenberg is active
     */
    function edac_gutenberg_active() {

      // return false if widgets page
      if (document.body.classList.contains("widgets-php")) return false;

      // check if block editor page
      return document.body.classList.contains("block-editor-page");
    }

    /**
     * Review Notice Ajax
     */
    if ($('.edac-review-notice').length) {
      $('.edac-review-notice-review').on('click', function () {
        edac_review_notice_ajax('stop', true);
      });

      $('.edac-review-notice-remind').on('click', function () {
        edac_review_notice_ajax('pause', false);
      });

      $('.edac-review-notice-dismiss').on('click', function () {
        edac_review_notice_ajax('stop', false);
      });
    }

    function edac_review_notice_ajax(review_action, redirect) {
      $.ajax({
        url: ajaxurl,
        method: 'GET',
        data: { action: 'edac_review_notice_ajax', review_action: review_action, nonce: edac_script_vars.nonce }
      }).done(function (response) {
        if (true === response.success) {
          let response_json = $.parseJSON(response.data);
          $('.edac-review-notice').fadeOut();
          if (redirect) {
            window.location.href = 'https://wordpress.org/support/plugin/accessibility-checker/reviews/#new-post';
          }
        } else {
          //console.log(response);
        }
      });
    }

    /**
     * Password Protected Notice Ajax
     */
    if ($('.edac_password_protected_notice').length) {
      $('.edac_password_protected_notice').on('click', function () {
        edac_password_protected_notice_ajax();
      });
    }

    function edac_password_protected_notice_ajax() {
      $.ajax({
        url: ajaxurl,
        method: 'GET',
        data: { action: 'edac_password_protected_notice_ajax', nonce: edac_script_vars.nonce }
      }).done(function (response) {
        if (true === response.success) {
          let response_json = $.parseJSON(response.data);
        } else {
          //console.log(response);
        }
      });
    }

    /**
     * GAAD Notice Ajax
     */
    if ($('.edac_gaad_notice').length) {
      $('.edac_gaad_notice').on('click', function () {
        edac_gaad_notice_ajax();
      });
    }

    function edac_gaad_notice_ajax() {
      $.ajax({
        url: ajaxurl,
        method: 'GET',
        data: { action: 'edac_gaad_notice_ajax', nonce: edac_script_vars.nonce }
      }).done(function (response) {
        if (true === response.success) {
          let response_json = $.parseJSON(response.data);
        } else {
          //console.log(response);
        }
      });
    }

    if ($('.edac-summary').length) {
      refresh_summary_and_readability();
    }
    if ($('.edac-details').length) {
      edac_details_ajax();
      ignore_submit();
    }
    if ($('.edac-details-rule-records-record-ignore').length) {
      ignore_submit();
    }
    if ($('.edac-readability').length) {
      refresh_summary_and_readability();
    }


    $('#dismiss_welcome_cta').on('click', function () {
      // AJAX request to handle button click
      $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
          action: 'edac_dismiss_welcome_cta_ajax',
        },
        success: function (response) {
          if (response === 'success') {
            // Hide the CTA on button click
            $('#edac_welcome_page_summary').hide();
          }
        }
      });
    });


    /**
     * Handle widget modal close click
     * @param {*} event
     */
    function edac_widget_modal_content_close(e) {

      var modal = e.target.closest('.edac-widget-modal');
      if (modal) {
        modal.style.display = 'none';
      }

      document.querySelector('.edac-summary').remove();

      $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
          action: 'edac_dismiss_dashboard_cta_ajax',
        }
      });


    }
    const modal_close_btn = document.querySelector('.edac-widget-modal-content-close');
    if (modal_close_btn) {
      modal_close_btn.addEventListener('click', edac_widget_modal_content_close);
    }


  });



})(jQuery);

window.addEventListener("load", function () {

  if (this.document.querySelector('.edac-widget .edac-summary')) {
    fillDashboardWidget();
  }

  edac_timestamp_to_local();


});


// Fill the dashboard widget
const fillDashboardWidget = () => {


  getData(edac_script_vars.edacApiUrl + '/scans-stats').then((data) => {
    if (data.success) {


      // Set passed %
      const passed_percentage = data.stats.passed_percentage;
      const passed_percentage_formatted = data.stats.passed_percentage_formatted;

      const passed_percentage_el = document.querySelector('#edac-summary-passed');
      if (passed_percentage_el) {
        passed_percentage_el.setAttribute('aria-valuenow', passed_percentage);
        passed_percentage_el.style.background =
          'radial-gradient(closest-side, white 85%, transparent 80% 100%), conic-gradient(#006600 ' + passed_percentage + '%, #e2e4e7 0)';
      }
      const passed_percentage_text_el = document.querySelector('#edac-summary-passed .edac-progress-percentage');
      if (passed_percentage_text_el) {
        passed_percentage_text_el.textContent = passed_percentage_formatted;
      }

      // Set completed_at
      const completed_at = data.stats.fullscan_completed_at;
      const completed_at_formatted = data.stats.fullscan_completed_at_formatted;
      const completed_at_el = document.querySelector('#edac-summary-info-date');
      completed_at_el.textContent = completed_at_formatted;

      /*
      const expires_at = data.stats.expires_at;
      const now = Date.now();
      const mins_to_exp = Math.round((expires_at - Math.floor(now / 1000))/60);
      const cache_hit = data.stats.cache_hit;
      if(completed_at_el && completed_at){
        completed_at_el.textContent = completed_at; 
        completed_at_el.setAttribute('data-edac-cache-hit', cache_hit);
        completed_at_el.setAttribute('data-edac-cache-mins-to-expiration', mins_to_exp + ' minutes');
      }
      */

      // scanned
      const posts_scanned = data.stats.posts_scanned;
      const posts_scanned_formatted = data.stats.posts_scanned_formatted;
      const posts_scanned_el = document.querySelector('#edac-summary-info-count');
      if (posts_scanned_el) {
        posts_scanned_el.textContent = posts_scanned_formatted;
      }


      // errors
      const errors = data.stats.distinct_errors_without_contrast;
      const errors_formatted = data.stats.distinct_errors_without_contrast_formatted;
      const errors_container_el = document.querySelector('.edac-summary-info-stats-box-error');
      if (errors > 0 && errors_container_el) {
        errors_container_el.classList.add('has-errors');
      }
      const errors_el = document.querySelector('#edac-summary-info-errors');
      if (errors_el) {
        errors_el.textContent = errors_formatted;
      }

      // constrast errors
      const contrast_errors = data.stats.distinct_contrast_errors;
      const contrast_errors_formatted = data.stats.distinct_contrast_errors_formatted;
      const contrast_container_el = document.querySelector('.edac-summary-info-stats-box-contrast');
      if (errors > 0 && contrast_container_el) {
        contrast_container_el.classList.add('has-errors');
      }
      const contrast_errors_el = document.querySelector('#edac-summary-info-contrast-errors');
      if (contrast_errors_el) {
        contrast_errors_el.textContent = contrast_errors_formatted;
      }


      // warnings
      const warnings = data.stats.distinct_warnings;
      const warnings_formatted = data.stats.distinct_warnings_formatted;
      const warnings_container_el = document.querySelector('.edac-summary-info-stats-box-warning');
      if (warnings > 0 && warnings_container_el) {
        warnings_container_el.classList.add('has-warning');
      }
      const warnings_el = document.querySelector('#edac-summary-info-warnings');
      if (warnings_el) {
        warnings_el.textContent = warnings_formatted;
      }


      // summary notice
      if (errors + contrast_errors + warnings > 0) {
        const has_issues_notice_el = document.querySelector('.edac-summary-notice-has-issues');
        if (has_issues_notice_el) {
          has_issues_notice_el.classList.remove('edac-hidden');
        }
      } else {
        const has_no_issues_notice_el = document.querySelector('.edac-summary-notice-no-issues');
        if (has_no_issues_notice_el && posts_scanned > 0) {
          has_no_issues_notice_el.classList.remove('edac-hidden');
        }
      }

      // truncated notice
      const is_truncated = data.stats.is_truncated;
      const is_truncated_el = document.querySelector('.edac-summary-notice-is-truncated');
      if (is_truncated_el && is_truncated) {
        is_truncated_el.classList.remove('edac-hidden');
      }


      const wrapper = document.querySelector('.edac-summary.edac-modal-container');
      if (wrapper) {
        wrapper.classList.remove('edac-hidden');
      }


      //edac_timestamp_to_local();

    }
  }).catch((e) => {
    //TODO:
  });


  getData(edac_script_vars.edacApiUrl + '/scans-stats-by-post-types').then((data) => {

    if (data.success) {

      Object.entries(data.stats).forEach(([key, value]) => {

        if (data.stats[key]) {

          const errors = value['distinct_errors_without_contrast'];
          const errors_formatted = value['distinct_errors_without_contrast_formatted'];
          const contrast_errors = value['distinct_contrast_errors'];
          const contrast_errors_formatted = value['distinct_contrast_errors_formatted'];
          const warnings = value['distinct_warnings'];
          const warnings_formatted = value['distinct_warnings_formatted'];

          const errors_el = document.querySelector('#' + key + '-errors');
          if (errors_el) {
            errors_el.textContent = errors_formatted;
          }

          const contrast_errors_el = document.querySelector('#' + key + '-contrast-errors');
          if (contrast_errors_el) {
            contrast_errors_el.textContent = contrast_errors_formatted;
          }

          const warnings_el = document.querySelector('#' + key + '-warnings');
          if (warnings_el) {
            warnings_el.textContent = warnings_formatted;
          }

        } else {
          //We aren't tracking stats for this post type

        }
      });

    }

    const wrapper = document.querySelector('.edac-issues-summary');
    if (wrapper) {
      wrapper.classList.remove('edac-hidden');
    }

    edac_timestamp_to_local();

  }).catch((e) => {
    console.log(e);
    //TODO:
  });

};



/**
   * Helper function to convert unixtime timestamp to the local date time.
   */
function edac_timestamp_to_local() {

  var options = { year: "numeric", month: "short", day: "numeric" };

  var elements = document.querySelectorAll(".edac-timestamp-to-local");

  elements.forEach(function (element) {

    if (/^[0-9]+$/.test(element.textContent)) { //if only numbers

      var unixtime_in_seconds = element.textContent;

      var d = new Date(unixtime_in_seconds * 1000).toLocaleDateString([], options);
      var t = new Date(unixtime_in_seconds * 1000).toLocaleTimeString([], { timeStyle: 'short' });

      var parts = Intl.DateTimeFormat([], { timeZoneName: 'short' }).formatToParts(new Date());
      let tz = '';
      for (const part of parts) {
        if (part.type === 'timeZoneName') {
          tz = part.value;
          break;
        }
      }

      element.innerHTML = '<span class="edac-date">' + d + '</span>&nbsp;<span class="edac-time">'
        + t + '</span>&nbsp;<span class="edac-timezone">' + tz + '</span>';

      element.classList.remove('edac-timestamp-to-local');

    }

  });


};


const getData = async (url = "", data = {}) => {
  const response = await fetch(url, {
    method: "GET",
    headers: {
      'X-WP-Nonce': edac_script_vars.restNonce
    }
  });
  return response.json();
}




const postData = async (url = "", data = {}) => {


  if (edac_script_vars.edacHeaders.Authorization == 'None') {
    return;
  }

  const response = await fetch(url, {
    method: "POST",
    headers: edac_script_vars.edacHeaders,
    body: JSON.stringify(data),
  });
  return response.json();
}

