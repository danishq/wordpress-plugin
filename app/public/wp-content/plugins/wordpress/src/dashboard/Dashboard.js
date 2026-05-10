import React, { useEffect, useState } from "react";
import ImageNoSearch from "../assets/images/no-search-results.png";
import { apiClient, getAppBaseUrl } from "../utils";
import AnalyticsCard from "./components/AnalyticsCards";
import PerformanceMetrics from "./components/PerformanceMetrics";
import QuickLinks from "./components/QuickLinks";
import RecentCard from "./components/RecentCards";

const Dashboard = () => {
  const [recentCampaigns, setRecentCampaigns] = useState([]);
  const [recentCarts, setRecentCarts] = useState([]);
  const [recentAutomations, setRecentAutomations] = useState([]);
  const [activities, setActivities] = useState([]);
  const [hasMoreActivities, setHasMoreActivities] = useState(true);
  const [pageNum, setPageNum] = useState(1);
  const [recoveryData, setRecoveryData] = useState({});
  const [campaignSales, setCampaignSales] = useState({});

  // Loading states
  const [isLoadingRecentCampaigns, setIsLoadingRecentCampaigns] =
    useState(false);
  const [isLoadingRecentCarts, setIsLoadingRecentCarts] = useState(false);
  const [isLoadingRecentAutomations, setIsLoadingRecentAutomations] =
    useState(false);
  const [isLoadingActivities, setIsLoadingActivities] = useState(false);
  const [isLoadingRecoveryData, setIsLoadingRecoveryData] = useState(false);
  const [isLoadingCampaignSales, setIsLoadingCampaignSales] = useState(false);

  const fetchRecentCampaigns = () => {
    setIsLoadingRecentCampaigns(true);
    apiClient("proxy/campaign?pageSize=5&pageNumber=1&status=COMPLETED")
      .then((response) => {
        setRecentCampaigns(response.data || []);
      })
      .catch((error) => {
        console.log("Error fetching recent campaigns:", error);
        setRecentCampaigns([]);
      })
      .finally(() => setIsLoadingRecentCampaigns(false));
  };

  const fetchRecentCarts = () => {
    setIsLoadingRecentCarts(true);
    apiClient("proxy/transactions?pageSize=5&pageNumber=1")
      .then((response) => {
        setRecentCarts(response.data || []);
      })
      .catch((error) => {
        console.log("Error fetching recent carts:", error);
        setRecentCarts([]);
      })
      .finally(() => setIsLoadingRecentCarts(false));
  };

  const fetchRecentAutomations = () => {
    setIsLoadingRecentAutomations(true);
    apiClient("proxy/automation?pageNumber=1&pageSize=5&status=ACTIVE")
      .then((data) => {
        if (data.totalElements > 0) {
          setRecentAutomations(data.data);
        } else {
          setRecentAutomations([]);
        }
      })
      .catch((err) => {
        console.log("Error fetching recent automations:", err);
        setRecentAutomations([]);
      })
      .finally(() => setIsLoadingRecentAutomations(false));
  };

  const fetchUserRecoveredAmount = () => {
    setIsLoadingRecoveryData(true);
    apiClient("proxy/transactions/recovered/amount")
      .then((data) => {
        setRecoveryData(data);
      })
      .catch((err) => {
        console.log("Error fetching user recovered amount:", err);
        setRecoveryData({});
      })
      .finally(() => setIsLoadingRecoveryData(false));
  };

  const fetchCampaignSales = () => {
    setIsLoadingCampaignSales(true);
    apiClient("proxy/campaign/sales")
      .then((data) => {
        setCampaignSales(data);
      })
      .catch((err) => {
        console.log("Error fetching campaign sales:", err);
        setCampaignSales({});
      })
      .finally(() => setIsLoadingCampaignSales(false));
  };

  const fetchActivities = (page = 1, append = false) => {
    setIsLoadingActivities(true);
    apiClient(`proxy/contact/activity?pageNum=${page}&pageSize=10`)
      .then((data) => {
        if (data.data.length === 0) {
          setHasMoreActivities(false);
        } else {
          setActivities((prev) =>
            append ? [...prev, ...data.data] : data.data,
          );
        }
      })
      .catch((error) => console.log("Error fetching activities:", error))
      .finally(() => setIsLoadingActivities(false));
  };

  const loadMoreActivities = () => {
    const nextPage = pageNum + 1;
    fetchActivities(nextPage, true);
    setPageNum(nextPage);
  };

  useEffect(() => {
    fetchRecentCampaigns();
    fetchRecentCarts();
    fetchRecentAutomations();
    fetchUserRecoveredAmount();
    fetchCampaignSales();
    fetchActivities();
  }, []);

  const redirectToNewTab = (url) => {
    window.open(`${getAppBaseUrl()}/app/${url}`, "_blank");
  };

  return (
    <div>
      <div className="home-container">
        <div className="home-details">
          <div className="cards-container">
            <div className="left-cards-container">
              <PerformanceMetrics />
              <RecentCard
                title="Recent Campaigns"
                linkText="View all Campaigns"
                imageText="No campaigns created"
                description="Once your campaign is published, you can start receiving data."
                buttonText="Create Campaign"
                iconSrc={ImageNoSearch}
                containerHeight={402}
                path="/campaigns"
                campaigns={recentCampaigns}
                linkAction={() => redirectToNewTab("campaigns")}
                isLoading={isLoadingRecentCampaigns}
              />
              <RecentCard
                title="Recent Carts"
                linkText="View all Carts"
                imageText="No carts created"
                buttonText="Connect your store"
                description="Whenever a cart is abandoned by a customer, we will immediately track it and display the cart details for you."
                iconSrc={ImageNoSearch}
                containerHeight={402}
                path="/app-integration"
                linkAction={() => redirectToNewTab("abandoned-cart")}
                carts={recentCarts}
                isLoading={isLoadingRecentCarts}
              />
              <RecentCard
                title="Recent Automations"
                linkText="View all Automations"
                imageText="No automations available"
                buttonText="Create Automation"
                description="Once your automation is published, you can start receiving data."
                iconSrc={ImageNoSearch}
                containerHeight={402}
                automations={recentAutomations}
                linkAction={() => redirectToNewTab("automations")}
                path="/automations"
                isLoading={isLoadingRecentAutomations}
              />
            </div>
            <div className="right-cards-container">
              <RecentCard
                title="Recent Activities"
                imageText="No recent acitvity"
                iconSrc={ImageNoSearch}
                containerHeight={618}
                activities={activities}
                onLoadMore={hasMoreActivities ? loadMoreActivities : null}
                isLoading={isLoadingActivities}
              />
              <AnalyticsCard
                title="Campaign Sales"
                centralValue={campaignSales.amountForCurrentMonth || "0"}
                centralLabel={campaignSales.month}
                lastMonth={campaignSales.amountForLastMonth || "0"}
                today={campaignSales.amountForLast24Hours || "0"}
                currency={campaignSales.currency}
                isLoading={isLoadingCampaignSales}
              />
              <AnalyticsCard
                title="Cart Recovery"
                centralValue={recoveryData.amountForCurrentMonth || "0"}
                centralLabel={recoveryData.month}
                lastMonth={recoveryData.amountForLastMonth || "0"}
                today={recoveryData.amountForLast24Hours || "0"}
                currency={recoveryData.currency}
                isLoading={isLoadingRecoveryData}
              />
              <QuickLinks />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
