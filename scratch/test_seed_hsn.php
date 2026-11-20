<?php
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../app/Helpers/Database.php';

$curatedHsn = [
    // 1. Raw Cotton, Wool, Silk, Natural Fibres & Yarns
    ['5201', 'Raw Cotton / Cotton, Not Carded or Combed', 5.00],
    ['5202', 'Cotton Waste (Including Yarn Waste and Garnetted Stock)', 5.00],
    ['5203', 'Cotton, Carded or Combed (Cotton Slivers)', 5.00],
    ['5204', 'Cotton Sewing Thread', 5.00],
    ['5205', 'Cotton Yarn (Combed/Carded Cotton Yarn >= 85% Cotton)', 5.00],
    ['5206', 'Cotton Yarn (< 85% Cotton, Blended Cotton Yarn)', 5.00],
    ['5207', 'Cotton Yarn Put Up for Retail Sale', 5.00],
    ['5004', 'Silk Yarn', 5.00],
    ['5007', 'Woven Fabrics of Silk or Silk Waste', 5.00],
    ['5105', 'Wool and Fine Animal Hair, Carded or Combed', 5.00],
    ['5106', 'Yarn of Carded Wool', 5.00],
    ['5107', 'Yarn of Combed Wool', 5.00],
    ['5111', 'Woven Fabrics of Carded Wool or Fine Animal Hair', 5.00],
    ['5112', 'Woven Fabrics of Combed Wool or Coarse Animal Hair', 5.00],
    ['5309', 'Woven Fabrics of Flax / Pure Linen Fabrics', 5.00],
    ['5310', 'Woven Fabrics of Jute or Bast Fibres', 5.00],

    // 2. Synthetic & Blended Filaments, Fibres & Yarns
    ['5401', 'Sewing Thread of Synthetic or Artificial Filaments', 12.00],
    ['5402', 'Synthetic Filament Yarn (Polyester, Nylon / Polyamide Filament Yarn)', 12.00],
    ['5403', 'Artificial Filament Yarn (Viscose Rayon Filament Yarn)', 12.00],
    ['5508', 'Sewing Thread of Man-Made Staple Fibres', 12.00],
    ['5509', 'Yarn of Synthetic Staple Fibres (Polyester Spun Yarn, Acrylic Yarn)', 12.00],
    ['5510', 'Yarn of Artificial Staple Fibres (Viscose Spun Yarn)', 12.00],

    // 3. Woven Fabrics
    ['5208', 'Woven Fabrics of Cotton (>= 85% Cotton, Weight <= 200 g/m² - Shirting, Poplin)', 5.00],
    ['5209', 'Woven Fabrics of Cotton (>= 85% Cotton, Weight > 200 g/m² - Denim, Canvas, Twill)', 5.00],
    ['5210', 'Woven Fabrics of Cotton Blended with Synthetic Fibres (Weight <= 200 g/m²)', 5.00],
    ['5211', 'Woven Fabrics of Cotton Blended with Synthetic Fibres (Weight > 200 g/m² - Twill, Suiting)', 5.00],
    ['5212', 'Other Woven Fabrics of Cotton', 5.00],
    ['5407', 'Woven Fabrics of Synthetic Filament Yarn (Polyester, Nylon, Microfibre Fabrics)', 5.00],
    ['5408', 'Woven Fabrics of Artificial Filament Yarn (Rayon, Modal, Cupro Fabrics)', 5.00],
    ['5512', 'Woven Fabrics of Synthetic Staple Fibres (100% Polyester Woven Fabric)', 5.00],
    ['5513', 'Woven Fabrics of Synthetic Fibres Mixed with Cotton (Weight <= 170 g/m²)', 5.00],
    ['5514', 'Woven Fabrics of Synthetic Fibres Mixed with Cotton (Weight > 170 g/m²)', 5.00],
    ['5515', 'Other Woven Fabrics of Synthetic Staple Fibres (Poly-Viscose PV Suiting)', 5.00],
    ['5516', 'Woven Fabrics of Artificial Staple Fibres (100% Viscose Woven Fabric)', 5.00],
    ['5801', 'Woven Pile and Chenille Fabrics (Velvet, Corduroy)', 5.00],
    ['5802', 'Terry Towelling and Similar Woven Terry Fabrics', 5.00],
    ['5806', 'Narrow Woven Fabrics (Elastic Webbing, Ribbons, Tapes, Bias Binding)', 5.00],
    ['5810', 'Embroidery in the Piece, in Strips or in Motifs', 5.00],

    // 4. Knitted or Crocheted Fabrics
    ['6001', 'Pile Fabrics, Long Pile & Terry Fabrics, Knitted or Crocheted (Velour, Fleece)', 5.00],
    ['6002', 'Knitted or Crocheted Fabrics (Width <= 30 cm, with Elastane / Rubber Thread)', 5.00],
    ['6004', 'Knitted Fabrics (Width > 30 cm, Containing >= 5% Elastane / Spandex / Lycra)', 5.00],
    ['6005', 'Warp Knit Fabrics (Tricot, Raschel Knit Fabrics)', 5.00],
    ['6006', 'Other Knitted Fabrics (Cotton Single Jersey, Interlock, Rib Knit, Pique, French Terry)', 5.00],

    // 5. Garments & Apparel - Knitted (Chapter 61)
    ['6101', "Men's or Boys' Overcoats, Anoraks, Wind-cheaters, Knitted", 5.00],
    ['6102', "Women's or Girls' Overcoats, Anoraks, Wind-cheaters, Knitted", 5.00],
    ['6103', "Men's or Boys' Suits, Ensembles, Jackets, Blazers, Trousers, Shorts, Knitted", 5.00],
    ['6104', "Women's or Girls' Suits, Ensembles, Dresses, Skirts, Trousers, Knitted", 5.00],
    ['6105', "Men's or Boys' Shirts, Knitted or Crocheted (Polo Shirts, Collared T-Shirts)", 5.00],
    ['6106', "Women's or Girls' Blouses, Shirts and Tops, Knitted or Crocheted", 5.00],
    ['6107', "Men's or Boys' Underpants, Briefs, Nightshirts, Pyjamas, Bathrobes, Knitted", 5.00],
    ['6108', "Women's or Girls' Slips, Petticoats, Briefs, Panties, Nightdresses, Pyjamas, Knitted", 5.00],
    ['6109', 'T-Shirts, Singlets, Tank Tops and Other Vests, Knitted or Crocheted', 5.00],
    ['6110', 'Jerseys, Pullovers, Cardigans, Sweatshirts, Hoodies, Knitted or Crocheted', 5.00],
    ['6111', "Babies' Garments and Clothing Accessories, Knitted or Crocheted", 5.00],
    ['6112', 'Track Suits, Ski Suits and Swimwear, Knitted or Crocheted', 5.00],
    ['6114', 'Other Garments, Knitted (Sports Tops, Gym Wear, Activewear, Uniforms)', 5.00],
    ['6115', 'Pantyhose, Tights, Stockings, Socks and Other Hosiery, Knitted', 5.00],
    ['6116', 'Gloves, Mittens and Mitts, Knitted or Crocheted', 5.00],
    ['6117', 'Other Made Up Clothing Accessories, Parts of Garments, Knitted (Collars, Ties, Shawls)', 5.00],

    // 6. Garments & Apparel - Woven (Chapter 62)
    ['6201', "Men's or Boys' Overcoats, Capes, Cloaks, Anoraks, Wind-jackets, Woven", 5.00],
    ['6202', "Women's or Girls' Overcoats, Capes, Cloaks, Anoraks, Wind-jackets, Woven", 5.00],
    ['6203', "Men's or Boys' Suits, Blazers, Formal Trousers, Denim Jeans, Shorts, Woven", 5.00],
    ['6204', "Women's or Girls' Suits, Jackets, Blazers, Dresses, Skirts, Kurtis, Salwars, Woven", 5.00],
    ['6205', "Men's or Boys' Shirts, Woven (Formal Shirts, Casual Cotton Shirts)", 5.00],
    ['6206', "Women's or Girls' Blouses, Shirts and Shirt-Blouses, Tops, Woven", 5.00],
    ['6207', "Men's or Boys' Singlets, Vests, Underpants, Briefs, Nightshirts, Pyjamas, Woven", 5.00],
    ['6208', "Women's or Girls' Singlets, Slips, Petticoats, Briefs, Nightdresses, Pyjamas, Woven", 5.00],
    ['6209', "Babies' Garments and Clothing Accessories, Woven", 5.00],
    ['6211', 'Track Suits, Swimwear, Uniforms, Aprons, Workwear, Woven', 5.00],
    ['6212', 'Brassieres, Girdles, Corsets, Braces, Suspenders and Similar Articles', 5.00],
    ['6214', 'Shawls, Scarves, Mufflers, Mantillas, Veils, Dupattas, Stoles, Woven', 5.00],
    ['6215', 'Ties, Bow Ties and Cravats, Woven', 5.00],
    ['6217', 'Other Made Up Clothing Accessories, Parts of Garments (Pockets, Cuffs, Collars)', 5.00],

    // 7. Made-Up Textile Articles & Home Textiles (Chapter 63)
    ['6302', 'Bed Linen, Table Linen, Toilet Linen and Kitchen Linen (Towels, Bedsheets, Pillow Covers)', 5.00],
    ['6304', 'Other Furnishing Articles (Curtains, Drapes, Cushion Covers)', 5.00],
    ['6305', 'Sacks and Bags Used for Packing of Goods (Cotton Packaging Bags, Jute Sacks)', 5.00],
    ['6307', 'Other Made Up Articles (Fabric Face Masks, Shopping Cloth Bags, Cleaning Rags)', 5.00],

    // 8. Trims, Accessories & Packaging
    ['9606', 'Buttons, Press-Fasteners, Snap-Fasteners, Studs, Button Blanks', 18.00],
    ['9607', 'Slide Fasteners (Zippers / Zips, Sliders, Pullers, Chain)', 18.00],
    ['5807', 'Woven Labels, Printed Fabric Labels, Badges and Patches', 12.00],
    ['4821', 'Paper & Paperboard Labels, Hang Tags, Price Tickets, Barcode Tags', 12.00],
    ['3923', 'Plastic Packaging Bags, Self-Adhesive Poly Bags, Plastic Hangers', 18.00],
    ['4819', 'Corrugated Cartons, Master Packing Boxes, Paperboard Packaging', 18.00],
    ['5607', 'Twine, Cordage, Ropes, Braided Drawstrings for Hoodies and Pants', 12.00],
    ['5602', 'Non-Woven Interlining, Fusible Interlining Felt for Collars and Cuffs', 12.00],

    // 9. Plant Machinery, Maintenance & Spare Parts
    ['8452', 'Sewing Machines (Industrial Single Needle, Overlock, Flatlock) & Sewing Machine Needles', 12.00],
    ['8451', 'Machinery for Washing, Ironing, Steam Pressing, Fusing, Drying & Fabric Finishing', 18.00],
    ['8448', 'Auxiliary Machinery & Spares for Textile Machines (Spindles, Healds, Reeds, Creels)', 18.00],
    ['8208', 'Cutting Machine Knives, Rotary Blades, Straight Blades, Band Knife Blades', 18.00],
    ['8213', 'Scissors, Tailor Shears, Thread Trimmers, Snips and Blades', 18.00],
    ['2710', 'Machine Lubricating Oil, Spindle Oil, White Mineral Oil for Sewing Machines', 18.00],
    ['8483', 'Transmission Shafts, Bearings, Gears, Pulleys, Belts and Machine Drives', 18.00],
    ['8421', 'Industrial Air Compressor Filters, Steam Iron Filters, Moisture Separators', 18.00],
    ['7318', 'Screws, Bolts, Nuts, Rivets, Fasteners for Sewing & Textile Machinery', 18.00],
    ['8487', 'Machinery Spare Parts, Gaskets, Seals, Mechanical Spares', 18.00],
];

echo "Curated items count: " . count($curatedHsn) . "\n";
