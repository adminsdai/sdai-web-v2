import { defineMiddleware } from "astro:middleware";

/**
 * Defense-in-depth boundary.
 * /motor is never treated as public content.
 * The concrete authenticated user must be populated by the auth/session layer.
 */
export const onRequest = defineMiddleware(async (context, next) => {
  const path = context.url.pathname;
  if (!path.startsWith("/motor")) return next();

  const user = context.locals.user;
  if (!user?.userId) {
    return new Response("Unauthorized", { status: 401 });
  }

  return next();
});
