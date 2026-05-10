import React from "react";
import { getAppBaseUrl } from "../../utils";

const ActivityDescription = ({ activity }) => {
  const redirectToNewTab = (url) => {
    window.open(`${getAppBaseUrl()}/app${url}`, "_blank");
  };

  const handleAbandonIdClick = (tab = 0) => {
    redirectToNewTab(`/abandoned-cart?tab=${tab}`);
  };

  const handleContactClick = (id, tab = 0) => {
    redirectToNewTab(`/contacts/allContacts/${id}?tab=${tab}`);
  };

  const handleCampaignClick = (id) => {
    redirectToNewTab(`/campaigns/charts/${id}`);
  };

  const linkStyle = {
    color: "#565ADD",
    textDecoration: "none",
    cursor: "pointer",
  };

  if (!activity) return null;

  switch (activity.type) {
    case "PROFILE_ADDED":
      return (
        <div>
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata?.contact_id);
            }}
          >
            {activity.metadata?.contact_name}
          </a>{" "}
          has recently joined
        </div>
      );

    case "NOTE_ADDED":
      return (
        <div>
          Added note on{" "}
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata?.contact_id);
            }}
          >
            {activity.metadata?.contact_name}
          </a>{" "}
          {activity.metadata?.note_description}
        </div>
      );

    case "ORDERED":
      return (
        <div>
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata?.contact_id);
            }}
          >
            {activity.metadata?.contact_name}
          </a>{" "}
          has made a new purchase, Order Id{" "}
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata.contact_id, 4);
            }}
          >
            #{activity.metadata?.order_id}
          </a>
        </div>
      );

    case "ABANDONED":
      return (
        <div>
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata?.contact_id);
            }}
          >
            {activity.metadata?.contact_name}
          </a>{" "}
          has abandoned the cart{" "}
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleAbandonIdClick();
            }}
          >
            #{activity.metadata?.transaction_id}
          </a>
        </div>
      );

    case "RECOVERED":
      return (
        <div>
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata?.contact_id);
            }}
          >
            {activity.metadata?.contact_name}
          </a>{" "}
          has recovered an abandoned order{" "}
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleAbandonIdClick(1);
            }}
          >
            #{activity.metadata?.transaction_id}
          </a>
        </div>
      );

    case "PUBLISHED":
      return (
        <div>
          Email campaign{" "}
          <a
            href="#"
            style={linkStyle}
            onClick={(e) => {
              e.preventDefault();
              handleCampaignClick(activity.metadata?.campaign_id);
            }}
          >
            {activity.metadata?.campaign_name}
          </a>{" "}
          is Published
        </div>
      );

    default:
      return null;
  }
};

export default ActivityDescription;
