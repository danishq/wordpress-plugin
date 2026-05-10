import { Chip, Typography } from "@mui/material";

import getSymbolFromCurrency from "currency-symbol-map";
import React, { useState } from "react";
import { ReactComponent as IconArrow } from "../../assets/icons/icon-back.svg";
import Progress from "../../components/Progress";
import Status from "../../components/Status";
import Table from "../../components/Table";
import { formatDate, formatStringToTitleCase, getAppBaseUrl } from "../../utils";
import ActivityDescription from "./RenderActivity";

export function getFirstWord(action) {
  if (!action) return action;

  return action.split("_")[0]; // Extracts the first word before the first underscore
}

export function getInitials(name) {
  if (!name || typeof name !== "string") return "";

  const words = name.trim().split(/\s+/); // Split by spaces, handling multiple spaces
  const firstInitial = words[0]?.charAt(0)?.toUpperCase() || "";
  const lastInitial =
    words?.length > 1
      ? words[words?.length - 1]?.charAt(0)?.toUpperCase() || ""
      : "";

  return firstInitial + lastInitial;
}

const RecentCard = ({
  title,
  buttonText,
  buttonAction,
  imageText,
  description,
  iconSrc,
  linkText,
  linkAction,
  containerHeight,
  path,
  activities = [],
  carts = [],
  campaigns = [],
  onLoadMore,
  automations = [],
  isLoading = false,
}) => {
  const [isLoadMoreLoading, setIsLoadMoreLoading] = useState(false);
  const cartTableHeaders = [
    {
      numeric: false,
      key: "name",
      disablePadding: false,
      label: "Name",
      render: (row) => (
        <Typography>
          {row?.metadata?.customer?.first_name ||
          row?.metadata?.customer?.last_name
            ? `${row?.metadata?.customer?.first_name || ""} ${
                row?.metadata?.customer?.last_name || ""
              }`.trim()
            : "-"}
        </Typography>
      ),
    },
    {
      numeric: false,
      key: "email",
      disablePadding: false,
      label: "Email",
      render: (row) => (
        <Typography>
          {row?.metadata?.customer?.email || row.contactEmail || "-"}
        </Typography>
      ),
    },
    {
      numeric: false,
      key: "created",
      disablePadding: false,
      label: "Created on",
      render: (row) =>
        formatDate(row?.metadata?.created_at || row.created, true),
    },
    {
      numeric: false,
      key: "amount",
      disablePadding: false,
      label: "Amount",
      render: (row) => (
        <div>
          {getSymbolFromCurrency(row?.metadata?.presentment_currency)}
          {row.amount}
        </div>
      ),
    },
    {
      numeric: false,
      key: "status",
      disablePadding: false,
      label: "Status",
      render: (row) => (
        <Chip
          label={formatStringToTitleCase(row.status)}
          size="small"
          sx={{
            backgroundColor: row.status === "RECOVERED" ? "#DCF2ED" : "#FFF4DE",
            color: row.status === "RECOVERED" ? "#09B29C" : "#B25E09",
          }}
        />
      ),
    },
  ];

  const campaignTableHeaders = [
    {
      numeric: false,
      key: "name",
      disablePadding: false,
      label: "Name",
      onClick: (row) => {
        if (row.status === "ON_GOING") {
          redirectToNewTab(`/campaigns/charts?id=${row.id}`);
        } else {
          redirectToNewTab(`/campaigns/${row.id}`);
        }
      },
      render: (row) => row.name,
    },
    {
      numeric: false,
      key: "created",
      disablePadding: false,
      label: "Created On",
      render: (row) => formatDate(row.created, true),
    },
    {
      numeric: false,
      key: "contacts",
      disablePadding: false,
      label: "Contacts",
    },
    {
      numeric: false,
      key: "sent",
      disablePadding: false,
      label: "Sent",
    },
    {
      numeric: false,
      key: "status",
      disablePadding: false,
      label: "Status",
      render: (row) => (
        <div className="table-column">
          {row.status ? (
            <div className={`status ${row.status}`}>
              {formatStringToTitleCase(row.status)}
            </div>
          ) : (
            "_"
          )}
        </div>
      ),
    },
  ];

  const automationTableHeaders = [
    {
      numeric: false,
      key: "name",
      disablePadding: false,
      label: "Name",
      onClick: (row) => redirectToNewTab(`/automations/${row.id}`),
      render: (row) => row.name,
    },
    {
      numeric: false,
      key: "description",
      disablePadding: false,
      label: "Description",
      render: (row) => row.description || "_",
    },
    {
      numeric: false,
      key: "created",
      disablePadding: false,
      label: "Created On",
      render: (row) => formatDate(row.created, true),
    },
    {
      numeric: false,
      key: "status",
      disablePadding: false,
      label: "Status",
      render: ({ status }) => (status ? <Status status={status} /> : "_"),
    },
  ];

  const redirectToNewTab = (url) => {
    window.open(`${getAppBaseUrl()}/app${url}`, "_blank");
  };

  const handleAbandonIdClick = (tab = 0) => {
    redirectToNewTab(`/abandoned-cart?tab=${tab}`);
  };

  const handleContactClick = (id, tab = 0) => {
    redirectToNewTab(`/contacts/allContacts/${id}?tab=${tab}`);
  };

  const activitySentance = (name, action, activity) => {
    if (
      !name ||
      !action ||
      typeof name !== "string" ||
      typeof action !== "string"
    )
      return "Invalid input";
    // Convert action to lowercase and replace underscores with spaces
    const formattedAction = action
      .split("_") // Split by underscore
      .slice(1) // Remove the first word (e.g., "EMAIL")
      .join(" ") // Join remaining words with spaces
      .toLowerCase();
    if (action === "EMAIL_SENT") {
      return (
        <div>
          send to{" "}
          <a
            href="#"
            style={{
              color: "#565ADD",
              textDecoration: "none",
              cursor: "pointer",
            }}
            onClick={(e) => {
              e.preventDefault();
              handleContactClick(activity.metadata?.contact_id);
            }}
          >
            {name}
          </a>
        </div>
      );
    }
    // return `${name} ${formattedAction} the email.`;
    return (
      <div>
        <a
          href="#"
          style={{
            color: "#565ADD",
            textDecoration: "none",
            cursor: "pointer",
          }}
          onClick={(e) => {
            e.preventDefault();
            handleContactClick(activity.metadata?.contact_id);
          }}
        >
          {name}{" "}
        </a>
        {formattedAction} the email.
      </div>
    );
  };

  const renderContent = () => {
    if (title === "Recent Campaigns" && campaigns.length > 0) {
      return (
        <div className="automation-card-body">
          <Table
            headers={campaignTableHeaders}
            rows={campaigns}
            noCheckBox={true}
            isLoading={false}
            ifEmpty={campaigns.length === 0}
          />
        </div>
      );
    }

    if (title === "Recent Carts" && carts.length > 0) {
      return (
        <div className="automation-card-body">
          <Table
            headers={cartTableHeaders}
            rows={carts}
            noCheckBox={true}
            isLoading={false}
            ifEmpty={carts.length === 0}
          />
        </div>
      );
    }

    if (title === "Recent Automations" && automations.length > 0) {
      return (
        <div className="automation-card-body">
          <Table
            headers={automationTableHeaders}
            rows={automations}
            noCheckBox={true}
            isLoading={false}
            ifEmpty={automations.length === 0}
          />
        </div>
      );
    }

    if (activities.length > 0) {
      return (
        <>
          <div className="automation-activity-log">
            <>
              {activities.map((activity) => (
                <div className="activity" key={activity.id}>
                  <div className="name-initial">
                    {getInitials(
                      activity?.metadata?.contact_name ||
                        activity?.metadata?.campaign_name,
                    )}
                  </div>
                  <div className="activity-description">
                    <p className="activity-message">
                      {getFirstWord(activity?.type) === "EMAIL" ? (
                        activitySentance(
                          activity?.metadata?.contact_name,
                          activity?.type,
                          activity,
                        )
                      ) : (
                        <ActivityDescription activity={activity} />
                      )}
                      {/* {renderActivityDescription(activity)} */}
                    </p>
                    <span className="activity-created">{activity.created}</span>
                  </div>
                </div>
              ))}
              {onLoadMore && (
                <div className="activity-footer">
                  <button
                    onClick={async () => {
                      try {
                        setIsLoadMoreLoading(true);
                        const maybePromise = onLoadMore();
                        if (
                          maybePromise &&
                          typeof maybePromise.then === "function"
                        ) {
                          await maybePromise;
                        }
                      } finally {
                        setIsLoadMoreLoading(false);
                      }
                    }}
                    className="btn no-border"
                    disabled={isLoadMoreLoading}
                  >
                    {isLoadMoreLoading ? "Loading..." : "Load More..."}
                  </button>
                </div>
              )}
            </>
          </div>
        </>
      );
    }

    return (
      <div className="automation-card-body">
        {iconSrc && (
          <img className="automation-icon" src={iconSrc} alt={`No ${title}`} />
        )}
        <p className="automation-description">{imageText}</p>
        <p className="automation-description">{description}</p>
        {buttonText && (
          <button
            className="btn btn-recent no-border"
            onClick={() => redirectToNewTab(path)}
          >
            {buttonText}
          </button>
        )}
      </div>
    );
  };

  return (
    <div
      className="automation-card-container"
      style={{
        height:
          !carts.length && !campaigns.length && !automations.length
            ? `${containerHeight}px`
            : "auto",
      }}
    >
      <div className="automation-card-header">
        <h3>{title}</h3>
        {linkText && (
          <button className="view-all-carts-btn" onClick={linkAction}>
            <span>{linkText}</span>
            <IconArrow className="icon24" />
          </button>
        )}
      </div>
      {isLoading ? <Progress /> : renderContent()}
    </div>
  );
};

export default RecentCard;
