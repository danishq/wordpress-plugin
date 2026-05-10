import React from "react";
import { ReactComponent as IconBack } from "../../assets/icons/icon-back.svg";
import { getAppBaseUrl } from "../../utils";

const QuickLinks = () => {
  const redirectToInNewTab = (url) => {
    window.open(`${getAppBaseUrl()}/app/${url}`, "_blank");
  };

  return (
    <div className="quick-link-container">
      <h3>Quick Links</h3>
      <div className="links">
        <div
          className="link"
          onClick={() => redirectToInNewTab("contacts/allContacts")}
        >
          <span>Contacts</span>
          <IconBack className="icon24" />
        </div>
        <div className="link" onClick={() => redirectToInNewTab("campaigns")}>
          <span>Email Campaigns</span>
          <IconBack className="icon24" />
        </div>
        <div
          className="link"
          onClick={() => redirectToInNewTab("abandoned-cart")}
        >
          <span>Carts</span>
          <IconBack className="icon24" />
        </div>
        <div className="link" onClick={() => redirectToInNewTab("automations")}>
          <span>Automations</span>
          <IconBack className="icon24" />
        </div>
      </div>
    </div>
  );
};

export default QuickLinks;
