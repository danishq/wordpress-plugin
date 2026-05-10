import React, { useEffect, useState } from "react";
import Progress from "../../components/Progress";
import { apiClient } from "../../utils";

// Define an array of metric data
function convertData(data) {
  return [
    { label: "Sent", value: data.numOfEmailSent || "-" },
    {
      label: "Delivered",
      value: data.deliveredPercentage || "-",
      number: data.numOfDeliveredEmail,
    },
    {
      label: "Open Rate",
      value: data.openPercentage || "-",
      number: data.numOfOpenedEmail,
    },
    {
      label: "Click Rate",
      value: data.clickPercentage || "-",
      number: data.numOfClickedEmail,
    },
    {
      label: "Click to Open Rate",
      value:
        data.numOfClickedEmail && data.numOfOpenedEmail
          ? `${((data.numOfClickedEmail / data.numOfOpenedEmail) * 100).toFixed(
              2,
            )}%`
          : "-",
    },
    { label: "Unsubscribe Rate", value: "-" },
    {
      label: "Bounce",
      value: data.bouncePercentage || "-",
      number: data.numOfBouncedEmail,
    },
    //  { label: "Marked as Spam", value: "-" },
    { label: "Revenue", value: "-" },
  ];
}

const PerformanceMetrics = () => {
  const [metrics, setMetrics] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  const fetchMetrics = () => {
    setIsLoading(true);
    apiClient("proxy/users/email/stats")
      .then((data) => {
        const temp = convertData(data);
        setMetrics(temp);
      })
      .catch((err) => {
        console.log("Error fetching metrics:", err);
      })
      .finally(() => setIsLoading(false));
  };

  useEffect(() => {
    fetchMetrics();
  }, []);

  return (
    <div className="performance-container">
      <h3 className="performance-title">Email Performance</h3>
      {isLoading ? (
        <Progress />
      ) : (
        <div class="metrics-grid">
          {metrics.map((metric, index) => (
            <div class="metric-item">
              <h3 className="metric-value">
                {metric.value}
                <span className="metric-number">
                  {metric?.number && ` (${metric.number})`}
                </span>
              </h3>
              <p className="metric-label">{metric.label}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default PerformanceMetrics;
