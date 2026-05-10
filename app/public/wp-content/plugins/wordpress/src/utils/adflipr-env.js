/**
 * App/marketing origin for links opened from the plugin UI.
 * Populated by PHP via wp_localize_script on the admin bundle (window.adfliprWp).
 */
const DEFAULT_APP_BASE = "https://adflipr.com";

export function getAppBaseUrl() {
  if (
    typeof window !== "undefined" &&
    window.adfliprWp &&
    window.adfliprWp.appBaseUrl
  ) {
    return String(window.adfliprWp.appBaseUrl).replace(/\/$/, "");
  }
  return DEFAULT_APP_BASE;
}
