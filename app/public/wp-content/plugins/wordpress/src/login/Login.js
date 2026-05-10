import { Box, Button, Container, Grid, Link, Typography } from "@mui/material";
import React, { useState } from "react";
import * as zod from "zod";
import { loginSchema } from "../api/validations";
import Progress from "../components/Progress";
import { apiClient, getAppBaseUrl } from "../utils";

const Login = ({ setIsLoggedIn }) => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    try {
      await loginSchema.parseAsync({ email, password });
      setErrors({});
    } catch (error) {
      if (error instanceof zod.ZodError) {
        const newErrors = {};
        error.issues.forEach((issue) => {
          newErrors[issue.path[0]] = issue.message;
        });
        setErrors(newErrors);
        return;
      } else {
        console.error("Unexpected error:", error);
        return;
      }
    } finally {
      setIsLoading(false);
    }

    try {
      setIsLoading(true);
      const response = await apiClient("login-token", "POST", null, {
        email: email,
        password: password,
      });

      if (!response.status) {
        throw new Error("Please enter valid login credentials");
      }

      // Reload the page to trigger App.js re-render with the new token
      setIsLoggedIn(true);
    } catch (error) {
      console.error("Error:", error);
      setError("Please enter valid login credentials");
    } finally {
      setIsLoading(false);
    }
  };

  const handleForgotPassword = () => {
    window.open(`${getAppBaseUrl()}/forgot-password/`, "_blank");
  };

  return (
    <Container component="main" className="form" maxWidth="xs">
      <Box
        sx={{
          marginTop: 8,
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
        }}
        className="form-container"
      >
        <Typography
          className="form-heading"
          component="h1"
          variant="h5"
          gutterBottom
        >
          Login for <strong>adflipr</strong>
        </Typography>
        <Box
          component="form"
          className="form-component login-form"
          noValidate
          onSubmit={handleSubmit}
          sx={{ mt: 3 }}
        >
          <Grid className="form-grid" container spacing={2}>
            <Grid className="form-grid-item" item xs={12}>
              {error && (
                <div className="input-container">
                  <Typography className="error-info">{error}</Typography>
                </div>
              )}
            </Grid>
            <Grid className="form-grid-item" item xs={12}>
              <div className="input-container">
                <label className="email-dialog-label">Email Id</label>
                <input
                  className={`email-dialog-input ${
                    errors?.email ? "box-error" : ""
                  }`}
                  placeholder="Type here..."
                  type="text"
                  onChange={(e) => {
                    setEmail(e.target.value);
                    setError("");
                    setErrors((prevErrors) => ({
                      ...prevErrors,
                      email: "",
                    }));
                  }}
                ></input>
                {errors?.email && (
                  <div className="error-msg">{errors?.email}</div>
                )}
              </div>
            </Grid>
            <Grid className="form-grid-item" item xs={12}>
              <div className="input-container">
                <label className="email-dialog-label">Password</label>
                <input
                  className={`email-dialog-input ${
                    errors?.password ? "box-error" : ""
                  }`}
                  placeholder="Type here..."
                  type="password"
                  onChange={(e) => {
                    setPassword(e.target.value);
                    setError("");
                    setErrors((prevErrors) => ({
                      ...prevErrors,
                      password: "",
                    }));
                  }}
                ></input>
                {errors?.password && (
                  <div className="error-msg">{errors?.password}</div>
                )}
              </div>
            </Grid>
          </Grid>
          <Button
            type="submit"
            fullWidth
            className="btn btn-primary btn-signup"
            variant="contained"
          >
            {isLoading ? <Progress button={true} /> : ""}
            <span
              style={{
                visibility: isLoading ? "hidden" : "visible",
              }}
            >
              Login
            </span>
          </Button>
        </Box>
        <Typography
          variant="body2"
          color="text.secondary"
          align="center"
          className="form-info"
          sx={{ mt: 2 }}
        >
          <Link href="#" color="#565add" onClick={handleForgotPassword}>
            Forgot Password?
          </Link>
        </Typography>
      </Box>
    </Container>
  );
};

export default Login;
