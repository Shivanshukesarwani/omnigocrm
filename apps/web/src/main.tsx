import React from "react";
import { createRoot } from "react-dom/client";
import "./styles.css";
function App(){return <main className="shell"><header><strong>OmniGoCRM</strong><span>Independent CRM platform</span></header><section><h1>One CRM. Every conversation. Every lead.</h1><p>OmniGoCRM is being built from the ground up with its own API, database and business logic.</p></section></main>}
createRoot(document.getElementById("root")!).render(<React.StrictMode><App/></React.StrictMode>);
