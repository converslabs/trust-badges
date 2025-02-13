export function Info() {
  const amazonPayImage = `${window.txBadgesSettings.pluginUrl}assets/images/needed/amazonpay.svg`;
  const amexColorImage = `${window.txBadgesSettings.pluginUrl}assets/images/needed/amexcolor.svg`;
  const anydayColorImage = `${window.txBadgesSettings.pluginUrl}assets/images/needed/anydaycolor.svg`;
  const applePay2ColorImage = `${window.txBadgesSettings.pluginUrl}assets/images/needed/applepay2color.svg`;

  return (
    <div className="space-y-4">
      <div className="mb-6 space-y-8">
        <div className="space-y-2">
          <h1 className="text-2xl font-bold">How It Works</h1>

          <div className="space-y-2 items-center grid grid-cols-4 gap-4">
            <img
              src={amazonPayImage}
              alt="Amazon Pay"
              className="w-full"
            />
            <img
              src={amexColorImage}
              alt="American Express"
              className="w-full"
            />
            <img
              src={anydayColorImage}
              alt="Anyday"
              className="w-full"
            />
            <img
              src={applePay2ColorImage}
              alt="Apple Pay"
              className="w-full"
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
