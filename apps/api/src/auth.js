import bcrypt from "bcryptjs";
import { query } from "./db.js";

export const hashPassword = (password) => bcrypt.hash(password, 12);
export const verifyPassword = (password, hash) => bcrypt.compare(password, hash);

export async function requireAuth(request) {
  await request.jwtVerify();
  const payload = request.user;
  const result = await query(
    "SELECT u.id,u.email,u.name,wm.workspace_id,wm.role FROM users u JOIN workspace_members wm ON wm.user_id=u.id WHERE u.id=$1 AND wm.workspace_id=$2",
    [payload.sub, payload.workspaceId]
  );
  if (!result.rowCount) {
    throw Object.assign(new Error("Invalid authentication context"), { statusCode: 401 });
  }
  request.authUser = {
    id: result.rows[0].id,
    email: result.rows[0].email,
    name: result.rows[0].name,
    workspaceId: result.rows[0].workspace_id,
    role: result.rows[0].role
  };
}

export function requireRole(...roles) {
  return async (request) => {
    if (!request.authUser || !roles.includes(request.authUser.role)) {
      throw Object.assign(new Error("Insufficient permissions"), { statusCode: 403 });
    }
  };
}
