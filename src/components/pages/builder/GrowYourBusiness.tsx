export function GrowYourBusiness() {
	return (
		<div className="space-y-6">
			<h1 className="text-2xl font-semibold">Grow your business in 17 simple steps</h1>
			
			<div className="text-sm text-muted-foreground">
				1 out of 17 completed
			</div>

			<div className="space-y-4">
				{/* Enhance Product Discovery Section */}
				<section className="border rounded-lg p-6">
					<div className="flex items-start justify-between">
						<div className="flex gap-4">
							<div className="w-16 h-16 bg-red-500 rounded-lg flex items-center justify-center text-white">
								<svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
								</svg>
							</div>
							<div>
								<h2 className="text-lg font-semibold">Boost AI Search & Filter</h2>
								<div className="flex items-center gap-1 mt-1">
									<div className="flex">
										{[...Array(5)].map((_, i) => (
											<svg key={i} className="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
												<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
											</svg>
										))}
									</div>
									<span className="text-sm">4.7 (+1963 reviews)</span>
								</div>
								<p className="text-sm text-gray-600 mt-1">
									Upgrade Site Search, Recommendation, Merchandising, and Navigation using AI
								</p>
							</div>
						</div>
						<button className="px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-800">
							Add app
						</button>
					</div>
					<div className="flex gap-6 mt-6 text-sm text-gray-500">
						<div className="flex items-center gap-2">
							<svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
							</svg>
							Average setup time: 15 minutes
						</div>
						<div className="flex items-center gap-2">
							<svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 11v-1M12 7a2.5 2.5 0 0 0 0-5 2.5 2.5 0 0 0 0 5zm0 12a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z" />
							</svg>
							Free plan available
						</div>
					</div>
				</section>

				{/* Shipping Address Validator Section */}
				<section className="border rounded-lg p-6">
					<div className="flex items-start justify-between">
						<div className="flex gap-4">
							<div className="w-16 h-16 bg-white border rounded-lg flex items-center justify-center">
								<svg className="w-10 h-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
								</svg>
							</div>
							<div>
								<h2 className="text-lg font-semibold">Shipping Address Validator</h2>
								<div className="flex items-center gap-1 mt-1">
									<div className="flex">
										{[...Array(5)].map((_, i) => (
											<svg key={i} className="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
												<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
											</svg>
										))}
									</div>
									<span className="text-sm">4.7 (+232 reviews)</span>
								</div>
								<p className="text-sm text-gray-600 mt-1">
									Address verification to crush delivery failures and delays!
								</p>
							</div>
						</div>
						<button className="px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-800">
							Add app
						</button>
					</div>
					<div className="flex gap-6 mt-6 text-sm text-gray-500">
						<div className="flex items-center gap-2">
							<svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
							</svg>
							Average setup time: 15 minutes
						</div>
						<div className="flex items-center gap-2">
							<svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 11v-1M12 7a2.5 2.5 0 0 0 0-5 2.5 2.5 0 0 0 0 5zm0 12a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z" />
							</svg>
							First 100 orders free
						</div>
					</div>
				</section>
			</div>
		</div>
	);
}

export default GrowYourBusiness;