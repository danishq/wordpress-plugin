document.addEventListener("DOMContentLoaded", function () {
  // Helper function to set a session cookie
  function setCookie(name, value) {
    // No expires attribute = session cookie (expires when browser closes)
    document.cookie =
      name + "=" + encodeURIComponent(value) + ";path=/;SameSite=Lax";
  }

  // Get the current URL when page loads
  function storeUTMParams() {
    const currentURL = window.location.href;

    // Parse the URL to get search parameters
    const parsedUrl = new URL(currentURL);
    const searchParams = new URLSearchParams(parsedUrl.search);

    // Loop over all query parameters
    for (const [key, value] of searchParams.entries()) {
      // Only store keys that start with "utm_"
      if (key.toLowerCase().startsWith("utm_") && value.trim() !== "") {
        // Store in session cookies (expires when browser closes)
        setCookie(key.toLowerCase(), value);
      }
    }
  }

  // Optional: Also track URL changes (for single page applications)
  window.addEventListener("popstate", function () {
    storeUTMParams(); // Fixed function name
  });

  // Call the function when DOM is ready
  storeUTMParams();
});
