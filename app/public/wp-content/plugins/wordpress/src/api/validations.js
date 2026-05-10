import * as zod from "zod";

const loginSchema = zod.object({
  email: zod
    .string()
    .email({ message: "Please enter valid email id" })
    .min(1, { message: "Please enter email id" }),
  password: zod
    .string()
    .min(6, { message: "Password must be at least 6 characters long" })
    .min(1, { message: "Please enter password" }),
});

export { loginSchema };
