import domReady from "@wordpress/dom-ready";
import { createRoot } from "@wordpress/element";
import App from "./App";

/**
 * Initialize the React application
 */
domReady(() => {
  const root = createRoot(document.getElementById("adflipr-wp-react-app"));
  root.render(<App />);
});
