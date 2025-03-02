import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"


export function Info() {
  // const payment = `${window.txBadgesSettings.pluginUrl}assets/images/needed/payment.png`;

  return (
    <div className="pt-8">
      <>

        <h1 className="text-lg font-bold">Documentation</h1>

        {/*<div className="space-y-2 items-center grid grid-cols-1 gap-4">*/}
          {/*<img*/}
          {/*  src={payment}*/}
          {/*  alt="American Express"*/}
          {/*  className="w-full h-full rounded"*/}
          {/*/>*/}
        {/*</div>*/}
        <Accordion type="single" collapsible>
          <AccordionItem value="item-1">
            <AccordionTrigger className="text-base font-medium">How It Works</AccordionTrigger>
            <AccordionContent>
              
              <p className="text-sm text-muted-foreground">
                The Trust Badges plugin enhances your eCommerce and any kind of
                site by displaying trust badges that can help increase customer
                confidence and conversion rates. The plugin is designed to be
                user-friendly and customizable, allowing you to easily add badges
                to your site and adjust their appearance and positioning.
              </p>

              <div className="pt-4 space-y-4">
                <ol className="list-inside list-decimal space-y-5 text-base">
                  <li>
                    <strong className="font-semibold">Enable the accordion:</strong>
                    <p className="pl-8 text-base text-gray-600">Ensure the main toggle switch at the top right corner is turned on to activate the each of the accordion.</p>
                  </li>
                  <li>
                    <strong className="font-semibold">Add New Badge:</strong>
                    <p className="pl-8 text-base text-gray-600">Click on the "Add New Badge" button located at the top right corner to start creating a new custom badge.</p>
                  </li>
                  <li className="space-y-3">
                    <strong className="font-semibold">Configure Header Settings:</strong>
                    <p className="pl-8 text-base text-gray-600">In the "Header Settings" section, customize the header text that appears above the badges.</p>
                    <ul className="list-inside list-disc space-y-1 pl-12 text-gray-600">
                      <li>Enter the desired text (e.g., "Secure Checkout With") in the "Header Text" field.</li>
                      <li>Adjust the font size using the input field provided.</li>
                      <li>Choose the alignment of the header text (left, center, right) from the options.</li>
                      <li>Select the color for the header text.</li>
                      <li><strong className="font-semibold">NOTE:</strong> The toggle button will show/hide the "Header Settings" from "Bar Preview".</li>
                    </ul>
                  </li>
                  <li className="space-y-3">
                    <strong className="font-semibold">Set Badge Placement:</strong>
                    <p className="pl-8 text-base text-gray-600">Under "Badge Placement," select where you want the badges to appear.</p>
                    <ul className="list-inside list-disc space-y-1 pl-12 text-gray-600">
                      <li>Check the boxes for the desired locations (e.g., WooCommerce, Easy Digital Downloads).</li>
                      <li>Use the shortcode provided to display the badges anywhere on your website.</li>
                      <li><strong className="font-semibold">NOTE:</strong> New custom accordions just bring "shortcode". Use this "shortcode" anywhere to show that badges.</li>
                    </ul>
                  </li>
                  <li className="space-y-3">
                    <strong className="font-semibold">Customize Badge Settings:</strong>
                    <p className="pl-8 text-base text-gray-600">In the "Badge Settings" section, choose the style and appearance of the badges.</p>
                    <ul className="list-inside list-disc space-y-1 pl-12 text-gray-600">
                      <li>Select from different styles like Original, Card, Mono, or Mono Card.</li>
                      <li>Choose the alignment of the badges (left, center, right).</li>
                      <li>Set the desktop and mobile sizes for the badges.</li>
                      <li>Pick a color for the badges.</li>
                      <li>Toggle the "Custom Margin" option if you want to add custom margins around the badges</li>
                      <li><strong className="font-semibold">NOTE 1:</strong> Select "Mono and Mono Card" to color the badges.</li>
                      <li><strong className="font-semibold">NOTE 2:</strong> Mobile sizes will effect below of width (768px).</li>
                    </ul>
                  </li>
                  <li>
                    <strong className="font-semibold">Preview and Select Badges:</strong>
                    <p className="pl-8 text-base text-gray-600">In the "Bar Preview" section, preview how the badges will look. At right-top, Select an "Animation" effect (e.g., Fade) to add dynamic behavior to the badges. Click "Play" to preview the animation effect</p>
                    <ul className="list-inside list-disc space-y-1 pl-12 text-gray-600">
                      <li>Click on "Select Badges" to choose which specific badges you want to display.</li>
                    </ul>
                  </li>
                  <li>
                    <strong className="font-semibold">Save Changes:</strong>
                    <p className="pl-8 text-base text-gray-600">After making all your selections and customizations, click on "Save All Changes" at the bottom right corner to apply the settings, and if you discard you changes then click on the "Reset" button.</p>
                  </li>
                  <li>
                    <strong className="font-semibold">Additional Sections:</strong>
                    <p className="pl-8 text-base text-gray-600">"Checkout, Product Page, and Footer" comes with by default and can not be deleted the default accordions. Toggling them on and off as required.</p>
                  </li>
                </ol>
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>

      </>

      {/* <div className="items-center space-y-4">
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
      </div> */}
    </div>
  );
}

export default Info;
