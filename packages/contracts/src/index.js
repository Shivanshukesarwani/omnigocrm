export const Roles = Object.freeze(["owner","admin","manager","agent","viewer"]);
export const Channels = Object.freeze(["whatsapp","sms","email","call","web"]);
export const LeadStatuses = Object.freeze(["new","contacted","qualified","converted","lost"]);

export function createAuthUser(data) { return data; }
export function createApiResponse(data) { return { data }; }
export function createPagination(limit, offset, count) { return { limit, offset, count }; }
export function createListResponse(data, pagination) { return { data, pagination }; }
