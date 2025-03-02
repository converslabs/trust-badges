import { Settings } from "./components/Settings";
import { Toaster } from "./components/ui/toaster";
import { Card } from "./components/ui/card";

function App() {
	return (
		<>
			<Card className="mt-6 mr-6">
				<div className="gap-6">
					<div className="py-8 px-8 max-w-full overflow-auto">
						<Settings />
					</div>
					{/*<div className="basis-1/4 py-12 pl-4 pr-8">*/}
					{/*	<Info />*/}
					{/*</div>*/}
				</div>
			</Card>
			<Toaster />
		</>
	);
}

export default App;
