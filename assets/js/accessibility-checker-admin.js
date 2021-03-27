(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  $(function () {

    // Accessibility Statement disable
    $("input[type=checkbox][name=edac_add_footer_accessibility_statement]").on('change',function () {
      if(this.checked) {
        $("input[type=checkbox][name=edac_include_accessibility_statement_link]").prop('disabled',false);
      }else{
        $("input[type=checkbox][name=edac_include_accessibility_statement_link]").prop('disabled',true);
        $("input[type=checkbox][name=edac_include_accessibility_statement_link]").prop('checked',false);
      }
      //
    });

    // Show Simplified Summary code on options page
    if ($("input[type=radio][name=edac_simplified_summary_position]:checked").val() == "none") {
      $("#ac-simplified-summary-option-code").show();
    }
    $("input[type=radio][name=edac_simplified_summary_position]").on('load',function () {
      if (this.value == "none") {
        $("#ac-simplified-summary-option-code").show();
      } else {
        $("#ac-simplified-summary-option-code").hide();
      }
    });
  });

  $(window).on('load',function () {
    /**
     * Tabs
     */
    $(".edac-tab").click(function (e) {
      e.preventDefault();
      var id = $("a", this).attr("href");

      $(".edac-panel").hide();
      $(".edac-panel").removeClass("active");
      $(".edac-tab a").removeClass("active");
      $(id).show();
      $(id).addClass("active");
      $("a", this).addClass("active");
    });

    // Details Tab on click Ajax
    /* $(".edac-tab-details").click(function (e) {
      edac_details_ajax();
    }); */

    // Summary Tab on click Ajax
    $(".edac-tab-summary").click(function (e) {
      edac_summary_ajax();
    });

    /**
     * Ajax Summary
     */
    function edac_summary_ajax() {
      //var post_id = wp.data.select("core/editor").getCurrentPostId();
      var post_id = edac_script_vars.postID;

      if (post_id == null) {
        return;
      }

      jQuery.post(
        ajaxurl,
        { action: "edac_summary_ajax", post_id: post_id, nonce: edac_script_vars.nonce},
        function (response) {
          var data = response;
          data = JSON.parse(data);
          $(".edac-summary").html(data);
        }
      );
    }

    /**
     * Ajax Details
     */
    function edac_details_ajax() {
      //var post_id = wp.data.select("core/editor").getCurrentPostId();
      var post_id = edac_script_vars.postID;

      if (post_id == null) {
        return;
      }

      jQuery.post(
        ajaxurl,
        { action: "edac_details_ajax", post_id: post_id, nonce: edac_script_vars.nonce },
        function (response) {
          var data = response;
          data = JSON.parse(data);
          //console.log(data);
          $(".edac-details").html(data);

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
          });

          // Ignore on click
          $(".edac-details-rule-records-record-actions-ignore").click(function (e) {
            e.preventDefault();
            $(this).parent().next(".edac-details-rule-records-record-ignore").slideToggle();
          });

          // Ignore submit on click
          ignore_submit();
          
        }
      );
    }

    /**
     * Ajax Readability
     */
    function edac_readability_ajax() {
      //var post_id = wp.data.select("core/editor").getCurrentPostId();
      var post_id = edac_script_vars.postID;

      if (post_id == null) {
        return;
      }

      jQuery.post(
        ajaxurl,
        { action: "edac_readability_ajax", post_id: post_id, nonce: edac_script_vars.nonce},
        function (response) {
          var data = response;
          data = JSON.parse(data);

          $(".edac-readability").html(data);

          // Simplified Summary on click
          $(".edac-readability-simplified-summary").submit(function (event) {
            event.preventDefault();

            //var post_id = wp.data.select("core/editor").getCurrentPostId();
            var summary = $("#edac-readability-text").val();

            jQuery.post(
              ajaxurl,
              {
                action: "edac_update_simplified_summary",
                post_id: post_id,
                summary: summary,
                nonce: edac_script_vars.nonce
              },
              function (response) {
                var data = response;
                data = JSON.parse(data);

                edac_readability_ajax();
                edac_summary_ajax();
                //console.log(data);
              }
            );
          });

          //console.log(readability);
        }
      );
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

          // Assume saving has finished

          // reset to first meta box tab
          /* $(".edac-panel").hide();
          $(".edac-panel").removeClass("active");
          $(".edac-tab a").removeClass("active");
          $("#edac-summary").show();
          $("#edac-summary").addClass("active");
          $(".edac-tab:first-child a").addClass("active"); */

          edac_summary_ajax();
          edac_details_ajax();
          edac_readability_ajax();
          $(".edac-panel").removeClass("edac-panel-loading");
        }
        lastIsSaving = isSaving;
      });
    }

    /**
     * Ignore Submit on click
     */
    function ignore_submit(){
      $(".edac-details-rule-records-record-ignore-submit").click(function (e) {
        e.preventDefault();
        var id = $(this).attr("data-id");
        var ignore_action = $(this).attr("data-action");
        var ignore_type = $(this).attr("data-type");
        var comment = $(
          ".edac-details-rule-records-record-ignore-comment",
          $(this).parent()
        ).val();

        jQuery.post(
          ajaxurl,
          {
            action: "edac_insert_ignore_data",
            id: id,
            comment: comment,
            ignore_action: ignore_action,
            ignore_type: ignore_type,
            nonce: edac_script_vars.nonce
          },
          function (response) {
            var data = response;
            data = JSON.parse(data);
            //console.log(data);

            var record = "#edac-details-rule-records-record-" + data["id"];
            var ignore_action = data["action"] == "enable" ? "disable" : "enable";
            var comment_disabled = data["action"] == "enable" ? true : false;
            var actions_ignore_label = data["action"] == "enable" ? "Ignored" : "Ignore";
            var ignore_submit_label = data["action"] == "enable" ? "Stop Ignoring" : "Ignore This " + data['type'];
            var username = data["user"] ? "<strong>Username:</strong> " + data["user"] : "";
            var date = data["date"] ? "<strong>Date:</strong> " + data["date"] : "";

            $(record + " .edac-details-rule-records-record-ignore-submit").attr("data-action", ignore_action);
            $(record + " .edac-details-rule-records-record-ignore-comment").attr("disabled", comment_disabled);
            if (data["action"] != "enable") {
              $(record + " .edac-details-rule-records-record-ignore-comment").val("");
            }
            $(record + " .edac-details-rule-records-record-actions-ignore").toggleClass("active");
            $(".edac-details-rule-records-record-actions-ignore[data-id='" + id + "']").toggleClass("active"); // pro
            $(record + " .edac-details-rule-records-record-actions-ignore-label").html(actions_ignore_label);
            $(".edac-details-rule-records-record-actions-ignore[data-id='" + id + "'] .edac-details-rule-records-record-actions-ignore-label").html(actions_ignore_label); // pro
            $(record + " .edac-details-rule-records-record-ignore-submit-label").html(ignore_submit_label);
            $(record + " .edac-details-rule-records-record-ignore-info-user").html(username);
            $(record + " .edac-details-rule-records-record-ignore-info-date").html(date);

            // Update rule count
            var rule = $(record).parents(".edac-details-rule");
            var count = parseInt($(".edac-details-rule-count", rule).html());
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

            // refresh page on ignore or unignore in pro
            if($('body').hasClass('accessibility-checker_page_accessibility_checker_issues') || $('body').hasClass('accessibility-checker_page_accessibility_checker_ignored')){
              location.reload(true);
            } 
          }
        );
      });
    }

    /**
     * Check if Gutenberg is active
     */
    function edac_gutenberg_active() {
      return document.body.classList.contains("block-editor-page");
    }

    edac_summary_ajax();
    edac_details_ajax();
    edac_readability_ajax();
    ignore_submit();

  });
})(jQuery);
