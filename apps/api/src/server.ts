import Fastify from "fastify";
import cors from "@fastify/cors";

const app = Fastify({ logger: true });
await app.register(cors, { origin: process.env.CORS_ORIGIN ?? "http://localhost:5173" });
app.get("/health", async () => ({ status: "ok", service: "omnigocrm-api", version: "0.1.0" }));
app.get("/api/v1", async () => ({ name: "OmniGoCRM API", version: "v1" }));
await app.listen({ port: Number(process.env.PORT ?? 3000), host: "0.0.0.0" });
