// REST client for Adflipr wp-json routes (requires cookie auth + X-WP-Nonce).

const ADFLIPR_TIMEOUT = 30;

function getRestNonce() {
  if (
    typeof window !== "undefined" &&
    window.adfliprWp &&
    window.adfliprWp.restNonce
  ) {
    return window.adfliprWp.restNonce;
  }
  return "";
}

export const apiClient = (
  path,
  method = "GET",
  params = null,
  body = null,
) => {
  let url = `/wp-json/adflipr/v1/${path}`;
  if (method === "GET" && params) {
    url += `?${new URLSearchParams(params)}`;
  }

  const nonce = getRestNonce();
  const headers = {
    "Content-Type": "application/json",
  };
  if (nonce) {
    headers["X-WP-Nonce"] = nonce;
  }

  const controller = new AbortController();
  const timeoutId = setTimeout(() => {
    controller.abort();
  }, ADFLIPR_TIMEOUT * 1000);

  return fetch(url, {
    method: method,
    headers: headers,
    body: body ? JSON.stringify(body) : null,
    credentials: "same-origin",
    signal: controller.signal,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      if (data && typeof data.body === "string") {
        try {
          return JSON.parse(data.body);
        } catch {
          return data;
        }
      }
      return data;
    })
    .catch((error) => {
      console.log("Error fetching data for:", url, error);
      throw error;
    })
    .finally(() => {
      clearTimeout(timeoutId);
    });
};
