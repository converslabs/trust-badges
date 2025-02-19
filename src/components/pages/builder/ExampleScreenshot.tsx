export function ExampleScreenshot() {
  
  const paymentBadges = `${window.txBadgesSettings.pluginUrl}assets/images/needed/paymentBadges.png`;
  const banks = `${window.txBadgesSettings.pluginUrl}assets/images/needed/banks.png`;
  const courierDelivery = `${window.txBadgesSettings.pluginUrl}assets/images/needed/courierDelivery.png`;

  return (
    <div className="pt-8 space-y-2">
      <div className="space-y-2">
          <h1 className="text-lg font-bold">Example Screenshots</h1>
        <div className="grid grid-cols-3 gap-4 pt-2">
        <img
            src={paymentBadges}
            alt="Payment Methods Badges"
            className="w-full h-full rounded"
          />
          <img
            src={banks}
            alt="Banks Badges"
            className="w-full h-full rounded"
          />
          <img
          src={courierDelivery}
          alt="Currier Delivery Badges"
          className="w-full h-full rounded"
        />
        </div>
      </div>
    </div>
  );
}

export default ExampleScreenshot;
