import pg from "pg";
const {Pool}=pg;
export const pool=new Pool({connectionString:process.env.DATABASE_URL});
export async function query(text,values=[]){return pool.query<T>(text,values);}
export async function withTransaction(fn){const c=await pool.connect();try{await c.query("BEGIN");const r=await fn(c);await c.query("COMMIT");return r}catch(e){await c.query("ROLLBACK");throw e}finally{c.release()}}