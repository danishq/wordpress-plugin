import { Button } from "@mui/material";
import { lazy, Suspense, useEffect, useState } from "@wordpress/element";
import Progress from "./components/Progress";
import "./styles/style.scss";
import { apiClient, getAppBaseUrl } from "./utils";

// Lazy load the larger components
const Dashboard = lazy(() => import("./dashboard/Dashboard"));
const Login = lazy(() => import("./login/Login"));

function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    setIsLoading(true);
    apiClient("login-token", "GET")
      .then((data) => {
        if (!data.loginToken) {
          throw new Error("No login token found");
        }
        setIsLoggedIn(true);
      })
      .catch((error) => {
        console.error("Failed to fetch login token:", error);
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  return (
    <div className="adflipr-app-container">
      <div className="adflipr-app-header">
        AdFlipr Overview
        {isLoggedIn && (
          <Button
            type="submit"
            variant="contained"
            color="primary"
            className="btn btn-primary"
            onClick={() => {
              window.open(`${getAppBaseUrl()}/app`, "_blank");
            }}
          >
            <span>View Details</span>
          </Button>
        )}
      </div>
      {isLoading ? (
        <Progress />
      ) : (
        <Suspense fallback={<Progress />}>
          {isLoggedIn ? <Dashboard /> : <Login setIsLoggedIn={setIsLoggedIn} />}
        </Suspense>
      )}
    </div>
  );
}

export default App;
