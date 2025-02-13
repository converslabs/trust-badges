import { Settings } from "./components/Settings";
import Info from "./components/Info";
import { Toaster } from "./components/ui/toaster";

function App() {
	return (
		<>
			<div className="bg-background flex flex-row gap-6">
				<div className="basis-3/4 py-12 px-4">
					<Settings />
				</div>
				<div className="basis-1/4 py-12 pl-4 pr-8">
					<Info />
				</div>
			</div>
			<Toaster />
		</>
	);
}

export default App;
