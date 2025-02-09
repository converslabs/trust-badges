import { Settings } from "./components/Settings";
import { Toaster } from "./components/ui/toaster";

function App() {
	return (
		<>
			<div className="bg-background">
				<div className="mx-auto max-w-7xl p-8">
					<Settings />
				</div>
			</div>
			<Toaster />
		</>
	);
}

export default App;
