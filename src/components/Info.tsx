export function Info() {
  const payment = `${window.txBadgesSettings.pluginUrl}assets/images/needed/payment.png`;

  return (
    <div className="space-y-4">
      <div className="mb-6 space-y-8">
        <div className="space-y-2">
          <h1 className="text-2xl font-bold">How It Works</h1>

          <div className="space-y-2 items-center grid grid-cols-1 gap-4">
            <img
              src={payment}
              alt="American Express"
              className="w-full h-full rounded"
            />
    
        </div>
          <div className="pt-8 space-y-4">
            <p className="text-base font-normal">
              Below are some information about how this plugin works and a little explanation.
            </p>
            <ul className="list-inside list-disc">
                <li><span className="font-bold">Hook:</span> Trust badge plugin used default WordPress "Hook" to show the badges</li>
                <li><span className="font-bold">Short Code:</span> Use the short codes to show the badges in your desired positions</li>
                <li><span className="font-bold">ON/OFF:</span> Each of the accordion will enable/disable by the main toggling button and inner works in that way too</li>
                <li><span className="font-bold">Position:</span> You can position the badges and heading</li>
                <li><span className="font-bold">Badges (Logo):</span> Select badges by clicking the "Select Badges" on the Bar Preview section</li>
                <li><span className="font-bold">Platform:</span> By default, we integrated WooCommerce & EDD</li>
            </ul>
          </div>
        </div>

        <div className="items-center space-y-4">
          <h1 className="text-xl font-bold">Build Your Badges</h1>

          <iframe
                width="100%"
                height="250"
                src="https://www.youtube.com/embed/156FSMbyMPQ"
                title="Trust Badges - Build guide line"
                frameBorder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
                className='rounded'
            ></iframe>
        </div>
      </div>
    </div>
  );
}

export default Info;
