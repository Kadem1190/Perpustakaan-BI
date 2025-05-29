document.addEventListener("DOMContentLoaded", () => {
  // Enable Bootstrap tooltips
  var bootstrap = window.bootstrap; // Declare the bootstrap variable
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })

  // Confirm delete actions
  document.querySelectorAll(".confirm-action").forEach(function(element) {
    element.addEventListener("click", function(e) {
      if (!confirm(this.getAttribute("data-confirm-message") || "Are you sure you want to perform this action?")) {
        e.preventDefault();
      }
    });
  });
});
