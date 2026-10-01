<?php
include('includes/session.inc');
$Title = _('MRP');


if(isset($_POST['REPORT'])){
    
}else{


include('includes/header.inc');
  
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


echo '<table class="table table-bordered"><tr><td><fieldset>Material Requirements Planning:<ul><li>
  MRP calculates and maintains an optimum manufacturing plan based on master production schedules,<br /> sales forecasts, inventory status, open orders and bills of material.<br />  If properly implemented, it will reduce cash flow and increase profitability. <br /> MRP will provide you with the ability to be pro-active rather than re-active in the management of your inventory levels and material flow.
 <br /> Implementing or improving Material Requirements Planning can provide the following benefits for your company:
 	</li><li>Reduced Inventory Levels
 	</li><li>Reduced Component Shortages
 	</li><li>Improved Shipping Performance
 	</li><li>Improved Customer Service
 	</li><li>Improved Productivity
 	</li><li>Simplified and Accurate Scheduling
 	</li><li>Reduced Purchasing Cost
 	</li><li>Improve Production Schedules
 	</li><li>Reduced Manufacturing Cost
 	</li><li>Reduced Lead Times
 	</li><li>Less Scrap and Rework
 	</li><li>Higher Production Quality
 	</li><li>Improved Communication
 	</li><li>Improved Plant Efficiency
 	</li><li>Reduced Freight Cost
 	</li><li>Reduction in Excess Inventory
 	</li><li>Reduced Overtime
 	</li><li>Improved Supply Schedules
 	</li><li>Improved Calculation of Material Requirements
 	</li><li>Improved Competitive Position</li>
        </ul></fieldset></td>
        <td valign="top"><fieldset>MPS INPUTS:<ul><li>
 	Forecast Demand</li><li>
 	Production Costs</li><li>
 	Inventory Costs</li><li>
 	Customer Orders</li><li>
 	Inventory Levels</li><li>
 	Supply</li><li>
 	Lot Size</li><li>
 	Production Lead Time</li><li>
 	Capacity</ul></fieldset><fieldset>
	MPS OUTPUT (production plan):<ul><li>
 	Amounts to be Produced</li><li>
 	Staffing Levels</li><li>
 	Quantity Available to Promise</li><li>
 	Projected Available Balance</li>
        </ul></fieldset>
        </td></tr><tr><td><input type="submit" name="REPORT" value="Generate MRP Report"/></tr></tr></table>';
 
echo '</form>';

include('includes/footer.inc');
}

?>