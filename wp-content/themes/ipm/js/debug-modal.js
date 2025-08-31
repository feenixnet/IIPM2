// Debug script to check modal functionality
console.log("=== MODAL DEBUG ===");

// Declare jQuery variable
const $ = window.jQuery;

// Check if jQuery is loaded
if (typeof $ !== "undefined") {
  console.log("✅ jQuery is loaded");

  // Check if modal elements exist
  const modalExists = $("#organisationModal").length > 0;
  console.log("Modal element exists:", modalExists);

  // Check if button exists
  const buttonExists = $("#addNewOrgBtn").length > 0;
  console.log("Add button exists:", buttonExists);

  // Test modal opening
  $(document).ready(() => {
    $("#addNewOrgBtn").on("click", (e) => {
      console.log("🔘 Add New Organisation button clicked");
      e.preventDefault();

      // Try to show modal
      $("#organisationModal").fadeIn(300);
      console.log("✅ Modal should be visible now");
    });

    // Close modal functionality
    $(".modal-close, .modal-backdrop").on("click", () => {
      console.log("🔘 Close modal clicked");
      $("#organisationModal").fadeOut(300);
    });
  });
} else {
  console.log("❌ jQuery is NOT loaded");
}
