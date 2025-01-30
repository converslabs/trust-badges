import { Settings } from "./components/Settings";
import { Toaster } from "./components/ui/toaster";

function App() {
	return (
		<>
			<div className="min-h-screen bg-background">
				<div className="mx-auto max-w-9xl p-8 mb-12">
					<Settings />
				</div>
			</div>
			<Toaster />
		</>
	);
}

export default App;
