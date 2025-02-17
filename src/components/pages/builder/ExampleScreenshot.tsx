export function ExampleScreenshot() {
  
  const free = `${window.txBadgesSettings.pluginUrl}assets/images/needed/free.png`;
  const ccourierDelivey = `${window.txBadgesSettings.pluginUrl}assets/images/needed/courierDelivey.png`;
  const trust = `${window.txBadgesSettings.pluginUrl}assets/images/needed/trust.png`;

  return (
    <div className="pt-8 space-y-2">
      <div className="space-y-2">
          <h1 className="text-lg font-bold">Example Screenshots</h1>
        <div className="grid grid-cols-3 gap-4">
          <img
            src={ccourierDelivey}
            alt="American Express"
            className="w-full h-full rounded"
          />
          <img
            src={trust}
            alt="American Express"
            className="w-full h-full rounded"
          />
          <img
            src={free}
            alt="American Express"
            className="w-full h-full rounded"
          />
        </div>
      </div>
    </div>
  );
}

export default ExampleScreenshot;
