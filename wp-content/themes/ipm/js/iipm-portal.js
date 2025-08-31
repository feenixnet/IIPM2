// IIPM Portal JS loaded
console.log("IIPM Portal JS loaded");

// Enhanced IIPM Portal JavaScript
// Use existing iipm_ajax from global scope

jQuery(document).ready(($) => {
  // User dropdown functionality
  $("#userDropdown").click((e) => {
    e.preventDefault();
    $("#userDropdownMenu").toggleClass("show");
    $(e.currentTarget).toggleClass("active");
  });

  // Close dropdown when clicking outside
  $(document).click((e) => {
    if (!$(e.target).closest(".user-dropdown").length) {
      $("#userDropdownMenu").removeClass("show");
      $("#userDropdown").removeClass("active");
    }
  });

  // Enhanced member registration form
  $("#iipm-member-registration-form").on("submit", function (e) {
    // Prevent the default form submission
    e.preventDefault();
    console.log("Form submission intercepted by AJAX handler");

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var formData = new FormData(this);
    formData.append("action", "iipm_register_member");

    // Validate passwords match
    var password = $form.find('input[name="password"]').val();
    var confirmPassword = $form.find('input[name="confirm_password"]').val();

    if (password !== confirmPassword) {
      showAlert("Passwords do not match", "error");
      return false;
    }

    // Show loading state
    $submitBtn.addClass("loading");
    $submitBtn.find(".btn-text").hide();
    $submitBtn.find(".btn-loading").show();

    // Debug log
    console.log("Sending AJAX request to: " + iipm_ajax.ajax_url);

    $.ajax({
      url: iipm_ajax.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: (response) => {
        console.log("AJAX response received:", response);

        if (response.success) {
          $form.hide();
          $("#registration-success").show();
          showAlert(
            "Registration successful! Please check your email.",
            "success"
          );
        } else {
          showAlert("Error: " + response.data, "error");
        }
      },
      error: (xhr, status, error) => {
        console.error("AJAX error:", error);
        showAlert("An error occurred. Please try again.", "error");
      },
      complete: () => {
        $submitBtn.removeClass("loading");
        $submitBtn.find(".btn-text").show();
        $submitBtn.find(".btn-loading").hide();
      },
    });

    // Return false to prevent normal form submission
    return false;
  });

  // Member type change handler
  $('input[name="member_type"]').change(function () {
    if ($(this).val() === "organisation") {
      $(".organisation-section").show();
    } else {
      $(".organisation-section").hide();
    }
  });

  // Enhanced bulk import form
  $("#iipm-bulk-import-form").submit(function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var formData = new FormData(this);
    formData.append("action", "iipm_bulk_import");

    // Validate file
    var fileInput = $form.find('input[type="file"]')[0];
    if (!fileInput.files.length) {
      showAlert("Please select a CSV file", "error");
      return;
    }

    var file = fileInput.files[0];
    if (file.size > 5 * 1024 * 1024) {
      showAlert("File size too large. Maximum 5MB allowed.", "error");
      return;
    }

    // Show loading state
    $submitBtn.addClass("loading");
    $form.hide();
    $("#import-progress").show();

    // Simulate progress
    var progress = 0;
    var progressInterval = setInterval(() => {
      progress += Math.random() * 15;
      if (progress > 90) progress = 90;
      $("#progress-fill").css("width", progress + "%");
      $("#progress-text").text("Processing... " + Math.round(progress) + "%");
    }, 500);

    $.ajax({
      url: iipm_ajax.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: (response) => {
        clearInterval(progressInterval);
        $("#progress-fill").css("width", "100%");
        $("#progress-text").text("Complete!");

        setTimeout(() => {
          $("#import-progress").hide();

          if (response.success) {
            var results = response.data;
            var html = '<div class="results-summary">';
            html +=
              '<div class="result-item"><strong>Total Records:</strong> ' +
              results.total +
              "</div>";
            html +=
              '<div class="result-item success"><strong>Successful:</strong> ' +
              results.successful +
              "</div>";
            html +=
              '<div class="result-item error"><strong>Failed:</strong> ' +
              results.failed +
              "</div>";
            html += "</div>";

            if (
              results.invitations_sent &&
              results.invitations_sent.length > 0
            ) {
              html += '<div class="invitations-sent">';
              html += "<h4>Invitations Sent To:</h4>";
              html += "<ul>";
              results.invitations_sent.forEach((email) => {
                html += "<li>" + email + "</li>";
              });
              html += "</ul>";
              html += "</div>";
            }

            if (results.errors && results.errors.length > 0) {
              html += '<div class="error-details">';
              html += "<h4>Errors:</h4>";
              html += "<ul>";
              results.errors.forEach((error) => {
                html += "<li>" + error + "</li>";
              });
              html += "</ul>";
              html += "</div>";
            }

            $("#results-content").html(html);
            $("#import-results").show();

            showAlert("Import completed successfully!", "success");
          } else {
            showAlert("Error: " + response.data, "error");
            $form.show();
          }
        }, 1000);
      },
      error: () => {
        clearInterval(progressInterval);
        showAlert(
          "An error occurred during import. Please try again.",
          "error"
        );
        $("#import-progress").hide();
        $form.show();
      },
      complete: () => {
        $submitBtn.removeClass("loading");
      },
    });
  });

  // File input display
  $("#csv_file").change(function () {
    var fileName = $(this)[0].files[0]
      ? $(this)[0].files[0].name
      : "Choose CSV file...";
    $(".file-text").text(fileName);
  });

  // Resend email verification
  $("#resend-verification").click((e) => {
    e.preventDefault();

    $.ajax({
      url: iipm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "iipm_resend_verification",
      },
      success: (response) => {
        if (response.success) {
          showAlert("Verification email sent successfully!", "success");
        } else {
          showAlert("Error: " + response.data, "error");
        }
      },
      error: () => {
        showAlert("An error occurred. Please try again.", "error");
      },
    });
  });

  // Handle resend verification button
  $(document).on("click", "#resend-verification-btn", function () {
    var btn = $(this);
    btn.text("Sending...").prop("disabled", true);

    // Note: This would need the user ID, which we'd need to store or get differently
    // For now, we'll show a message
    setTimeout(() => {
      showAlert("Please log in first to resend verification email.", "info");
      btn.text("Resend Verification Email").prop("disabled", false);
    }, 1000);
  });

  // Profile update form
  $("#profile-update-form").submit(function (e) {
    e.preventDefault();

    var $form = $(this);
    var formData = $form.serialize() + "&action=iipm_update_profile";

    $.ajax({
      url: iipm_ajax.ajax_url,
      type: "POST",
      data: formData,
      success: (response) => {
        if (response.success) {
          showAlert("Profile updated successfully!", "success");
          if (response.data.completion) {
            updateProfileCompletion(response.data.completion);
          }
        } else {
          showAlert("Error: " + response.data, "error");
        }
      },
      error: () => {
        showAlert("An error occurred. Please try again.", "error");
      },
    });
  });

  // Bulk import modal
  $("#bulk-import-btn").click(() => {
    $("#bulk-import-modal").show();
  });

  // View organisation members
  $("#view-members-btn").click(() => {
    $.ajax({
      url: iipm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "iipm_get_org_members",
      },
      success: (response) => {
        if (response.success) {
          displayOrganisationMembers(response.data);
        } else {
          showAlert("Error: " + response.data, "error");
        }
      },
      error: () => {
        showAlert("An error occurred. Please try again.", "error");
      },
    });
  });

  // View errors modal
  $(".view-errors").click(function () {
    var errors = $(this).data("errors");
    $("#error-content").text(errors);
    $("#error-modal").show();
  });

  // Close modal functionality
  $(".modal-close").click(function () {
    $(this).closest(".modal").hide();
  });

  // Close modal when clicking outside
  $(".modal").click(function (e) {
    if (e.target === this) {
      $(this).hide();
    }
  });

  // Progress bar animations
  $(".progress-fill").each(function () {
    var $this = $(this);
    var width = $this.css("width");
    $this.css("width", "0%");
    setTimeout(() => {
      $this.css("width", width);
    }, 500);
  });

  // Auto-hide alerts after 5 seconds
  setTimeout(() => {
    $(".alert").fadeOut();
  }, 5000);

  // Smooth scrolling for anchor links
  $('a[href^="#"]').click(function (e) {
    e.preventDefault();
    var target = $($(this).attr("href"));
    if (target.length) {
      $("html, body").animate(
        {
          scrollTop: target.offset().top - 100,
        },
        500
      );
    }
  });

  // Form validation helpers
  function validateEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  // Real-time form validation
  $('input[type="email"]').blur(function () {
    var email = $(this).val();
    if (email && !validateEmail(email)) {
      $(this).addClass("error");
      if (!$(this).next(".error-message").length) {
        $(this).after(
          '<div class="error-message">Please enter a valid email address</div>'
        );
      }
    } else {
      $(this).removeClass("error");
      $(this).next(".error-message").remove();
    }
  });

  // Password strength indicator
  $('input[name="password"]').on("input", function () {
    var password = $(this).val();
    var strength = calculatePasswordStrength(password);
    updatePasswordStrengthIndicator(strength);
  });

  // Helper functions
  function showAlert(message, type) {
    var alertClass = type === "success" ? "alert-success" : "alert-error";
    var alertHtml =
      '<div class="alert ' + alertClass + '">' + message + "</div>";

    // Remove existing alerts
    $(".alert").remove();

    // Add new alert
    $("body").prepend(alertHtml);

    // Auto-hide after 5 seconds
    setTimeout(() => {
      $(".alert").fadeOut();
    }, 5000);
  }

  function updateProfileCompletion(completion) {
    $(".progress-fill").css("width", completion + "%");
    $(".completion-percentage").text(completion + "%");
  }

  function calculatePasswordStrength(password) {
    var strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
  }

  function updatePasswordStrengthIndicator(strength) {
    var indicator = $(".password-strength");
    if (!indicator.length) {
      $('input[name="password"]').after(
        '<div class="password-strength"></div>'
      );
      indicator = $(".password-strength");
    }

    var strengthText = ["Very Weak", "Weak", "Fair", "Good", "Strong"];
    var strengthClass = ["very-weak", "weak", "fair", "good", "strong"];

    indicator
      .removeClass()
      .addClass("password-strength " + strengthClass[strength - 1]);
    indicator.text(strengthText[strength - 1] || "");
  }

  function displayOrganisationMembers(members) {
    var html = '<div class="members-list">';
    html += "<h3>Organisation Members</h3>";
    html += '<table class="members-table">';
    html +=
      "<thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Last Login</th></tr></thead>";
    html += "<tbody>";

    members.forEach((member) => {
      html += "<tr>";
      html += "<td>" + member.display_name + "</td>";
      html += "<td>" + member.user_email + "</td>";
      html +=
        '<td><span class="status-' +
        member.membership_status +
        '">' +
        member.membership_status +
        "</span></td>";
      html +=
        "<td>" +
        (member.last_login
          ? new Date(member.last_login).toLocaleDateString()
          : "Never") +
        "</td>";
      html += "</tr>";
    });

    html += "</tbody></table></div>";

    // Create and show modal
    var modal = $(
      '<div class="modal"><div class="modal-content">' +
        html +
        '<button class="modal-close">Close</button></div></div>'
    );
    $("body").append(modal);
    modal.show();
  }
});
