function merge_options(obj1, obj2) {
    var obj3 = {};
    for (var attrname in obj1) {
        obj3[attrname] = obj1[attrname];
    }
    for (var attrname in obj2) {
        obj3[attrname] = obj2[attrname];
    }
    return obj3;
}

function escapeRegExp(string) {
    return string.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
}

function replaceAll(find, replace, str) {
    return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
}

function getExplorerRisks() {
    var risks = new Array();
    Ext.Array.each($$('qrmID-RiskTable').getSelectionModel().getSelection(), function (item) {
        risks.push(item.data);
    });
    return risks;
}

function getExplorerRiskIDs() {
    var risks = new Array();
    Ext.Array.each($$('qrmID-RiskTable').getSelectionModel().getSelection(), function (item) {
        risks.push(item.data.riskID);
    });
    return risks;
}

function checkExplorerSelection() {
    return ($$('qrmID-RiskTable').getSelectionModel().getSelection().length > 0);
}

function exportSVG(id) {

    var form = Ext.getBody().createChild({
        tag: 'form',
        method: 'POST',
        action: "./exportSVGtoPNG",
        children: [
            {
                tag: 'input',
                type: 'hidden',
                name: "svg"
       }]
    });

    // Assign the data on the value so it doesn't get messed up in the html insertion
    form.last(null, true).value = document.getElementById(id).innerHTML;

    form.dom.submit();
    form.remove();
}

function matrixFilter(impact, prob, treated, tol) {


    QRM.app.getExplorerController().matrixFilter({
        "DESCENDANTS": $$('cbDescendants').value,
        "PROJECTID": QRM.global.projectID,
        "OPERATION": "getRiskLiteFetch",
        "TREATEDPROB": treated ? prob : -1,
        "TREATEDIMPACT": treated ? impact : -1,
        "UNTREATEDPROB": treated ? -1 : prob,
        "UNTREATEDIMPACT": treated ? -1 : impact,
        "TREATED": treated
    });

    var selectedCellSelector = "rect.qrmMatElementID" + impact + prob;

    QRM.global.selectedCellProb = prob;
    QRM.global.selectedCellImpact = impact;
    QRM.global.selectedCellTol = tol;
    QRM.global.selectedCellTreated = treated;

    if (treated) {
        selectedCellSelector = selectedCellSelector + "T";
    } else {
        selectedCellSelector = selectedCellSelector + "U";
    }

    d3.select(selectedCellSelector).attr("class", "selectedMatCell");
}

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function parseDate(input) {
    var parts = input.split(' ')[0].split('-');
    return new Date(parts[0], parts[1] - 1, parts[2]);
}

function Map() {
    // members
    this.keyArray = new Array();
    // Keys
    this.valArray = new Array();
    // Values
    this.put = function (key, val) {
        var elementIndex = this.findIt(key);
        if (elementIndex == (-1)) {
            this.keyArray.push(key);
            this.valArray.push(val);
        } else {
            this.valArray[elementIndex] = val;
        }
    };
    this.get = function (key) {
        var result = null;
        var elementIndex = this.findIt(key);
        if (elementIndex != (-1)) {
            result = this.valArray[elementIndex];
        }
        return result;
    };
    this.remove = function (key) {
        var elementIndex = this.findIt(key);
        if (elementIndex != (-1)) {
            this.keyArray = this.keyArray.removeAt(elementIndex);
            this.valArray = this.valArray.removeAt(elementIndex);
        }
        return;
    };
    this.size = function () {
        return (this.keyArray.length);
    };
    this.clear = function () {
        while (this.keyArray.length > 0) {
            this.keyArray.pop();
            this.valArray.pop();
        }
    };
    this.keySet = function () {
        return (this.keyArray);
    };
    this.valSet = function () {
        return (this.valArray);
    };
    this.showMe = function () {
        var result = "";
        for (var i = 0; i < this.keyArray.length; i++) {
            result += "Key: " + this.keyArray[i] + "\tValues: " + this.valArray[i] + "\n";
        }
        return result;
    };
    this.findIt = function (key) {
        var result = (-1);
        for (var i = 0; i < this.keyArray.length; i++) {
            if (this.keyArray[i] == key) {
                result = i;
                break;
            }
        }
        return result;
    };
    this.removeAt = function (index) {
        var part1 = this.slice(0, index);
        var part2 = this.slice(index + 1);
        return (part1.concat(part2));
    };

}

function calcProb(risk, preMit) {


    var startMom = moment(risk.start);
    var endMom = moment(risk.end);
    var days = (new Date(risk.end).getTime() - new Date(risk.start).getTime()) / (1000 * 60 * 60 * 24);
    var alpha = 0;
    var T = 0;
    var type = 0;

    if (preMit) {
        type = Number(risk.likeType);
    } else {
        type = Number(risk.likePostType);
    }

    try {
        if (type == 4) {
            if (preMit) {
                return risk.likeProb * 100;
            } else {
                return risk.likePostProb * 100;
            }
        } else {
            if (preMit) {
                alpha = risk.likeAlpha;
                T = risk.likeT;
            } else {
                alpha = risk.likePostAlpha;
                T = risk.likePostT;
            }

            var alphat = alpha * (days / T);
            var prob = 1 - (Math.exp(-alphat) * ((Math.pow(alphat, 0) / fact(0))));
            return prob * 100;
        }
    } catch (e) {
        alert(e.message);
        return -1.0;
    }
}

function fact(n) {
    if (n == 0) {
        return 1;
    }
    return n * fact(n - 1);
}

function probFromMatrix(qprob, mat) {

    // The the risk likelihood parameters to match the matrix settings.
    var lowerLimit = 0.0;
    var upperLimit = 0.0;
    switch (parseInt(Math.floor(qprob), 10)) {
    case 1:
        lowerLimit = 0.0;
        upperLimit = mat.probVal1;
        break;
    case 2:
        lowerLimit = mat.probVal1;
        upperLimit = mat.probVal2;
        break;
    case 3:
        lowerLimit = mat.probVal2;
        upperLimit = mat.probVal3;
        break;
    case 4:
        lowerLimit = mat.probVal3;
        upperLimit = mat.probVal4;
        break;
    case 5:
        lowerLimit = mat.probVal4;
        upperLimit = mat.probVal5;
        break;
    case 6:
        lowerLimit = mat.probVal5;
        upperLimit = mat.probVal6;
        break;
    case 7:
        lowerLimit = mat.probVal6;
        upperLimit = mat.probVal7;
        break;
    case 8:
        lowerLimit = mat.probVal7;
        upperLimit = mat.probVal8;
        break;
    }

    var prob = lowerLimit + (upperLimit - lowerLimit) * (qprob - Math.floor(qprob));
    return prob;

}

function probToMatrix(prob, mat) {

    var qprob = 0.5;

    if (mat.probVal1 != null && 0 <= prob && prob <= mat.probVal1 && mat.maxProb >= 1) {
        qprob = 1.0 + (prob / mat.probVal1);
    }
    if (mat.probVal1 != null && mat.probVal2 != null && mat.probVal1 < prob && prob <= mat.probVal2 && mat.maxProb >= 2) {
        qprob = 2.0 + ((prob - mat.probVal1) / (mat.probVal2 - mat.probVal1));
    }
    if (mat.probVal2 != null && mat.probVal3 != null && mat.probVal2 < prob && prob <= mat.probVal3 && mat.maxProb >= 3) {
        qprob = 3.0 + ((prob - mat.probVal2) / (mat.probVal3 - mat.probVal2));
    }
    if (mat.probVal3 != null && mat.probVal4 != null && mat.probVal3 < prob && prob <= mat.probVal4 && mat.maxProb >= 4) {
        qprob = 4.0 + ((prob - mat.probVal3) / (mat.probVal4 - mat.probVal3));
    }
    if (mat.probVal4 != null && mat.probVal5 != null && mat.probVal4 < prob && prob <= mat.probVal5 && mat.maxProb >= 5) {
        qprob = 5.0 + ((prob - mat.probVal4) / (mat.probVal5 - mat.probVal4));
    }
    if (mat.probVal5 != null && mat.probVal6 != null && mat.probVal5 < prob && prob <= mat.probVal6 && mat.maxProb >= 6) {
        qprob = 6.0 + ((prob - mat.probVal5) / (mat.probVal6 - mat.probVal5));
    }
    if (mat.probVal6 != null && mat.probVal7 != null && mat.probVal6 < prob && prob <= mat.probVal7 && mat.maxProb >= 7) {
        qprob = 7.0 + ((prob - mat.probVal6) / (mat.probVal7 - mat.probVal6));
    }
    if (mat.probVal7 != null && mat.probVal8 != null && mat.probVal7 < prob && prob <= mat.probVal8 && mat.maxProb == 8) {
        qprob = 8.0 + ((prob - mat.probVal7) / (mat.probVal8 - mat.probVal7));
    }

    return qprob;
}

function setMatrix(tolString, maxImpact, maxProb, val, svgDivID, treated, clkCallBack) {

    var margin = {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0
    };
    var width = 180 - margin.left - margin.right;
    var height = 180 - margin.top - margin.bottom;

    var data = new Array();

    for (var prob = maxProb; prob > 0; prob--) {
        for (var impact = 1; impact <= maxImpact; impact++) {
            var index = (prob - 1) * maxImpact + impact - 1;
            var tol = tolString.substring(index, index + 1);
            data.push({
                "impact": impact,
                "prob": prob,
                "tol": tol,
                "val": val[(prob - 1) * maxImpact + impact - 1],
                "treated": treated
            });
        }
    }


    var gridSizeX = Math.floor(width / maxImpact);
    var gridSizeY = Math.floor(height / maxProb);

    d3.select(svgDivID + " svg").remove();

    var svg = d3.select(svgDivID).append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var heatMap = svg.selectAll(".hour")
        .data(data)
        .enter().append("g")
        .attr("class", "tolCell")
        .attr("id", function (d) {
            return d.impact + "-" + d.prob;
        });

    heatMap.append("rect")
        .attr("x", function (d) {
            return (d.impact - 1) * gridSizeX;
        })
        .attr("y", function (d) {
            return (maxProb - d.prob) * gridSizeY;
        })
        .attr("rx", 2)
        .attr("ry", 2)
        .attr("class", function (d) {
            var root = "tol" + d.tol + " qrmMatElementID" + d.impact + d.prob;
            if (d.treated) {
                return root + "T";
            } else {
                return root + "U";
            }
        })
        .attr("width", gridSizeX)
        .attr("height", gridSizeY)
        .on("click", function (d) {
            clkCallBack(d.impact, d.prob, d.treated, d.tol);
        });

    heatMap.append("text")
        .attr("x", function (d) {
            return (d.impact - 1) * gridSizeX + gridSizeX / 2;
        })
        .attr("y", function (d) {
            return (maxProb - d.prob) * gridSizeY + gridSizeY / 2 + 5;
        })
        .attr("class", "tolText")
        .attr('pointer-events', 'none')
        .style("text-anchor", "middle")
        .on("click", function (d) {
            clkCallBack(d.impact, d.prob, d.treated, d.tol);
        })
        .text(function (d) {
            return (d.val > 0) ? d.val : "";
        });
}

function resetSelectedCell() {
    //Reset Current Selected Cell
    var resetClassName = "tol" + QRM.global.selectedCellTol + " qrmMatElementID" + QRM.global.selectedCellImpact + QRM.global.selectedCellProb;
    if (QRM.global.selectedCellTreated) {
        resetClassName = resetClassName + "T";
    } else {
        resetClassName = resetClassName + "U";
    }

    d3.select("rect.selectedMatCell").attr("class", resetClassName);
}

function preInit() {

    // Add move to front capability to d3
    d3.selection.prototype.moveToFront = function () {
        return this.each(function () {
            this.parentNode.appendChild(this);
        });
    };


    Array.prototype.clear = function () {
        this.length = 0;
    };
    Array.prototype.findAll = function (field, value) {
        var subArray = new Array();
        Ext.Array.each(this, function (item) {
            if (item.field == value) {
                subArray.push(item);
            }
        });
        return subArray;
    };
    Array.prototype.getProperty = function (field) {
        res = new Array();
        Ext.Array.each(this, function (item) {
            res.push(item[field]);
        });
        return res;
    };
    Array.prototype.getDataProperty = function (field) {
        res = new Array();
        Ext.Array.each(this, function (item) {
            res.push(item.data[field]);
        });
        return res;
    };

    BrowserDetect.init();

}

function setRiskEditorMatrix(risk, matrixConfig, matrixDIVID, matrixDisplayConfig, dragStartCallback, dragCallback, dragEndCallback) {

    var radius = matrixDisplayConfig.radius;
    var tolString = matrixConfig.tolString;
    var maxImpact = matrixConfig.maxImpact;
    var maxProb = matrixConfig.maxProb;

    var margin = {
        top: radius * 2,
        right: radius * 2,
        bottom: radius * 2,
        left: radius * 2
    };
    var width = matrixDisplayConfig.width - 2 * radius;
    var height = width;


    var data = new Array();

    for (var prob = maxProb; prob > 0; prob--) {
        for (var impact = 1; impact <= maxImpact; impact++) {
            var index = (prob - 1) * maxImpact + impact - 1;
            var tol = tolString.substring(index, index + 1);
            data.push({
                "impact": impact,
                "prob": prob,
                "tol": tol
            });
        }
    }

    var gridSizeX = Math.floor(width / maxImpact);
    var gridSizeY = Math.floor(height / maxProb);


    //Create the matrix
    d3.selectAll("#" + matrixDIVID + " svg").remove();

    var topSVG = d3.select("#" + matrixDIVID).append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom);

    //Need to embed the style into the SVG element so it can be interpreted by the PNGTranscoder on the server
    topSVG.append("defs")
        .append("style")
        .attr("type", "text/css")
        .text(
            "rect.tolNoHover5 {fill: #ff0000;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover4 {fill: #ffa500;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover3 {fill: #ffff00;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover2 {fill: #00ff00;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover1 {fill: #00ffff; stroke: #E6E6E6; stroke-width: 2px; }" +
            "g.riskEditorRiskUntreated text.untreated { fill:red; font: 12px sans-serif; font-weight : bold; pointer-events : none; }" +
            "g.riskEditorRiskTreated text.treated { fill:blue; font: 12px sans-serif; font-weight : bold; pointer-events : none; }"

        );

    var svg = topSVG
        .append("g")
        .attr("class", "riskEditorMatrixHolder")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ") ");

    var heatMap = svg.selectAll()
        .data(data)
        .enter().append("g")
        .attr("class", "tolCellNoHover");

    // This is the matrix itself
    heatMap.append("rect")
        .attr("x", function (d) {
            return (d.impact - 1) * gridSizeX;
        })
        .attr("y", function (d) {
            return (maxProb - d.prob) * gridSizeY;
        })
        .attr("rx", 2)
        .attr("ry", 2)
        .attr("class", function (d) {
            return "tolNoHover" + d.tol;
        })
        .attr("width", gridSizeX)
        .attr("height", gridSizeY);

    // Behavior of the indicators when they are dragged
    var drag = d3.behavior.drag()
        .on("dragstart", function () {
            dragStartCallback();
        })
        .on("drag", function () {
            d3.select(this).attr("transform", function (d, i) {
                var node = d3.select(this);
                var treated = node.attr("treated") == "true";

                if (treated) {
                    d.x1 += d3.event.dx;
                    d.y1 += d3.event.dy;

                    if (d.x1 < 0) {
                        d.x1 = 0;
                    }
                    if (d.y1 < 0) {
                        d.y1 = 0;
                    }
                    if (d.x1 > width) {
                        d.x1 = width;
                    }
                    if (d.y1 > height) {
                        d.y1 = height;
                    }

                    d.treatedImpact = 1 + (d.x1 / gridSizeX);
                    d.treatedProb = (maxProb + 1) - (d.y1 / gridSizeX);

                    dragCallback({
                        impact: d.treatedImpact,
                        prob: d.treatedProb,
                        treated: true
                    });

                    //                  var prob = ((d.treatedProb-1)/maxProb)*100;
                    //                   $$('qrm-RiskEditorProbProbTreatedID').setValue(prob.toFixed(2)+"%");
                    //                   $$('qrm-RiskEditorProbImpactTreatedID').setValue(Math.floor(d.treatedImpact));

                    return "translate(" + [d.x1, d.y1] + ")";

                } else {

                    d.x += d3.event.dx;
                    d.y += d3.event.dy;

                    if (d.x < 0) {
                        d.x = 0;
                    }
                    if (d.y < 0) {
                        d.y = 0;
                    }
                    if (d.x > width) {
                        d.x = width;
                    }
                    if (d.y > height) {
                        d.y = height;
                    }

                    d.inherentImpact = 1 + (d.x / gridSizeX);
                    d.inherentProb = (maxProb + 1) - (d.y / gridSizeX);

                    dragCallback({
                        impact: d.inherentImpact,
                        prob: d.inherentProb,
                        treated: false
                    });
                    var prob = ((d.inherentProb - 1) / maxProb) * 100;
                    //                   $$('qrm-RiskEditorProbProbUnTreatedID').setValue(prob.toFixed(2)+"%");
                    //                   $$('qrm-RiskEditorProbImpactUnTreatedID').setValue(Math.floor(d.inherentImpact));
                    //                    dragCallback();

                    return "translate(" + [d.x, d.y] + ")";
                }
            });
        })
        .on("dragend", function (d) {

            d.dirty = true;

            var node = d3.select(this);
            var treated = node.attr("treated") == "true";

            if (treated) {
                d.treatedImpact = 1 + (d.x1 / gridSizeX);
                d.treatedProb = (maxProb + 1) - (d.y1 / gridSizeX);
                //              var prob = ((d.treatedProb-1)/maxProb)*100;
                dragEndCallback({
                    impact: d.treatedImpact.toFixed(2),
                    prob: d.treatedProb.toFixed(2),
                    treated: true
                });
                //               $$('qrm-RiskEditorProbProbTreatedID').setValue(prob.toFixed(2)+"%");
                //                alert("Treated: "+prob+"  "+d.treatedImpact);

            } else {
                d.inherentImpact = 1 + (d.x / gridSizeX);
                d.inherentProb = (maxProb + 1) - (d.y / gridSizeX);
                //               var prob = ((d.untreatedProb-1)/maxProb)*100;
                dragEndCallback({
                    impact: d.inherentImpact.toFixed(2),
                    prob: d.inherentProb.toFixed(2),
                    treated: false
                });
                //                $$('qrm-RiskEditorProbProbUnTreatedID').setValue(prob.toFixed(2)+"%");
                //                 alert("UnTreated: "+prob+"  "+d.untreatedImpact);
            }

        });

    //Initial position of the indicators
    var Xn = (risk.inherentImpact - 1) / maxImpact;
    var Yn = (risk.inherentProb - 1) / maxProb;

    risk.x = Xn * width;
    risk.y = (1 - Yn) * height;

    var Xn1 = (risk.treatedImpact - 1) / maxImpact;
    var Yn1 = (risk.treatedProb - 1) / maxProb;

    risk.x1 = Xn1 * width;
    risk.y1 = (1 - Yn1) * height;

    var untreatedRisk = svg.selectAll().data([risk]).enter()
        .append("g")
        .style("cursor", "move")
        .attr("transform", "translate(" + [risk.x, risk.y] + ")")
        .attr("treated", false)
        .attr("class", "riskEditorRiskUntreated")
        .call(drag);
    untreatedRisk.append("circle").style("fill", "white").attr({
        r: radius
    });
    untreatedRisk.append("circle").style("fill", "red").attr({
        r: radius - 2
    });
    untreatedRisk.append("circle").style("fill", "white").attr({
        r: radius - 4
    });
    untreatedRisk.append("text").attr({
            'text-anchor': 'middle',
            y: 4
        })
        .attr("class", "untreated")
        .style("font-size", "8px")
        .text(function (d) {
            return d.riskProjectCode;
        });

    var treatedRisk = svg.selectAll().data([risk]).enter()
        .append("g")
        .style("cursor", "move")
        .attr("transform", "translate(" + [risk.x1, risk.y1] + ")")
        .attr("treated", true)
        .attr("class", "riskEditorRiskTreated")
        .call(drag);
    treatedRisk.append("circle").style("fill", "white").attr({
        r: radius
    });
    treatedRisk.append("circle").style("fill", "blue").attr({
        r: radius - 2
    });
    treatedRisk.append("circle").style("fill", "white").attr({
        r: radius - 4
    });
    treatedRisk.append("text").attr({
            'text-anchor': 'middle',
            y: 4
        })
        .attr("class", "treated")
        .style("font-size", "8px")
        .text(function (d) {
            return d.riskProjectCode;
        });


    // Set the form values
    //          var prob = ((risk.inherentProb-1)/maxProb)*100;
    //          $$('qrm-RiskEditorProbProbUnTreatedID').setValue(prob.toFixed(2)+"%");
    //          $$('qrm-RiskEditorProbImpactTreatedID').setValue(Math.floor(risk.inherentImpact));
    //
    //          prob = ((this.risk.treatedProb-1)/maxProb)*100;
    //          $$('qrm-RiskEditorProbProbTreatedID').setValue(prob.toFixed(2)+"%");
    //          $$('qrm-RiskEditorProbImpactUnTreatedID').setValue(Math.floor(risk.inherentImpact));

    svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "14px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [-10, height / 2] + ") rotate(-90)")
        .text("Probability");

    svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "14px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [width / 2, height + 20] + ")")
        .text("Impact");


}

function getProjectStakeholders(p) {

    var s = [];
    // Create a list of stakeholders
    try {

        p.riskOwners.forEach(function (e) {
            s.push(e)
        });
        p.riskManagers.forEach(function (e) {
            s.push(e)
        });

    } catch (e) {
        console.log(e.message);
    }

    // Remove any Duplicate 
    var arr = {};
    for (var i = 0; i < s.length; i++)
        arr[s[i]['email']] = s[i];

    var temp = new Array();
    for (var key in arr)
        temp.push(arr[key]);

    return temp;


}

function minimiseSideBar() {
    $("body").toggleClass("mini-navbar");
    if (!$('body').hasClass('mini-navbar') || $('body').hasClass('body-small')) {
        // Hide menu in order to smoothly turn on when maximize menu
        $('#side-menu').hide();
        // For smoothly turn on menu
        setTimeout(
            function () {
                $('#side-menu').fadeIn(500);
            }, 100);
    } else if ($('body').hasClass('fixed-sidebar')) {
        $('#side-menu').hide();
        setTimeout(
            function () {
                $('#side-menu').fadeIn(500);
            }, 300);
    } else {
        // Remove all inline style from jquery fadeIn function to reset menu state
        $('#side-menu').removeAttr('style');
    }

    //Resize things that may have be impacted
    setTimeout(
        function () {
            $(window).trigger('resize');
        }, 250);
}

d3.gantt = function(clickCallBack) {
   var FIT_TIME_DOMAIN_MODE = "fit";

   var margin = {top : 20,right : 40, bottom : 20,     left : 150 };
   var timeDomainStart = d3.time.day.offset(new Date(),-3);
   var timeDomainEnd = d3.time.hour.offset(new Date(),+3);
   var timeDomainMode = FIT_TIME_DOMAIN_MODE;// fixed or fit
   var taskTypes = [];
   var taskStatus = [];

   var height = null;
   var width = null;
   var tickFormat = "%b %Y";

   var keyFunction = function(d) { return d.startDate + d.taskName + d.endDate; };
   var rectTransform = function(d) {return "translate(" + x(d.startDate) + "," + y(d.taskName) + ")"; };

   var x = d3.time.scale().domain([ timeDomainStart, timeDomainEnd ]).range([ 0, width ]).clamp(true);
   var y = d3.scale.ordinal().domain(taskTypes).rangeRoundBands([ 0, height - margin.top - margin.bottom ], .1);

   var xAxis = d3.svg.axis().scale(x).orient("bottom").tickFormat(d3.time.format(tickFormat)).tickSubdivide(true).tickSize(8).tickPadding(8);
   var yAxis = d3.svg.axis().scale(y).orient("left").tickSize(0);

   var initTimeDomain = function(tasks) {
      if (timeDomainMode === FIT_TIME_DOMAIN_MODE) {
         if (tasks === undefined || tasks.length < 1) {
            timeDomainStart = d3.time.day.offset(new Date(), -3);
            timeDomainEnd = d3.time.hour.offset(new Date(), +3);
            return;
         }
         tasks.sort(function(a, b) {return a.endDate - b.endDate; });
         timeDomainEnd = tasks[tasks.length - 1].endDate;

         tasks.sort(function(a, b) {return a.startDate - b.startDate; });
         timeDomainStart = tasks[0].startDate;
      }
   };

   var initAxis = function() {
      x = d3.time.scale().domain([ timeDomainStart, timeDomainEnd ]).range([ 0, width ]).clamp(true);
      y = d3.scale.ordinal().domain(taskTypes).rangeRoundBands([ 0, height - margin.top - margin.bottom ], .1);
      xAxis = d3.svg.axis().scale(x).orient("bottom").tickFormat(d3.time.format(tickFormat)).tickSubdivide(true).tickSize(8).tickPadding(8);
      yAxis = d3.svg.axis().scale(y).orient("left").tickSize(0);
   };


   function gantt(tasks, svgdiv, w,h) {

      height = h - margin.top - margin.bottom-5;
      width = w - margin.right - margin.left-5;

   //   var dateFormatter = Ext.util.Format.dateRenderer('d M Y');

      initTimeDomain(tasks);
      initAxis();

      var svg = d3.select(svgdiv)
      .append("svg")
      .attr("class", "chart")
      .attr("width", width + margin.left + margin.right)
      .attr("height", height + margin.top + margin.bottom)
      .append("g")
      .attr("class", "gantt-chart")
      .attr("width", width + margin.left + margin.right)
      .attr("height", height + margin.top + margin.bottom)
      .attr("transform", "translate(" + margin.left + ", " + margin.top + ")");

      svg.selectAll(".chart")
      .data(tasks, keyFunction).enter()
      .append("rect")
      .attr("rx", 5)
      .attr("ry", 5)
      .attr("class", function(d){ return d.className; }) 
      .attr("y", 0)
      .attr("transform", rectTransform)
      .attr("height", function(d) { return y.rangeBand(); })
      .attr("width", function(d) { return (x(d.endDate) - x(d.startDate));})
      .on('mouseover', function(d) { 
//          try {
//                tooltipProb.text(d.taskName+" - Start Exposure: "+dateFormatter(d.startDate)+", End Exposure: "+dateFormatter(d.endDate));
//              } catch(e){
//                  alert(e.message);
//              } return tooltipProb.style("visibility", "visible"); 
      })
      .on("mousemove", function () { return tooltipProb.style("top", (event.pageY - 9) + "px").style("left", (event.pageX + 20) + "px");  })
      .on("mouseout", function () { return tooltipProb.style("visibility", "hidden");}) 
      .on("click", function (d) { 
          clickCallBack(d.riskID);
//         var e = d3.event;
//         if( !e.ctrlKey) return;
//         QRM.app.getCalenderController().listenForEditorChanges = true;
//         getRiskCodeAndDisplayInt(d.taskName);
       }); 

      svg.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0, " + (height - margin.top - margin.bottom) + ")")
      .transition()
      .call(xAxis);

      var title =  "Project Titke";
      svg.append("text")
      .attr("text-anchor", "middle")
      .style("font-size", "20px")
      .style("font-weight", "normal")
      .attr("transform", "translate(" + [ width/2, 4 ] + ")")
      .text( title);
      


      svg.append("g").attr("class", "y axis").transition().call(yAxis);

      return gantt;

   }

   gantt.redraw = function(tasks) {

      initTimeDomain(tasks);
      initAxis();

      var svg = d3.select("svg");

      var ganttChartGroup = svg.select(".gantt-chart");
      var rect = ganttChartGroup.selectAll("rect").data(tasks, keyFunction);

      rect.enter()
      .insert("rect",":first-child")
      .attr("rx", 5)
      .attr("ry", 5)
      .attr("class", "bar") 
      .transition()
      .attr("y", 0)
      .attr("transform", rectTransform)
      .attr("height", function(d) { return y.rangeBand(); })
      .attr("width", function(d) { 
         return (x(d.endDate) - x(d.startDate)); 
      }) ;

      rect.transition()
      .attr("transform", rectTransform)
      .attr("height", function(d) { return y.rangeBand(); })
      .attr("width", function(d) { 
         return (x(d.endDate) - x(d.startDate)); 
      });

      rect.exit().remove();

      svg.select(".x").transition().call(xAxis);
      svg.select(".y").transition().call(yAxis);

      return gantt;
   };

   gantt.margin = function(value) {
      if (!arguments.length)
         return margin;
      margin = value;
      return gantt;
   };

   gantt.timeDomain = function(value) {
      if (!arguments.length)
         return [ timeDomainStart, timeDomainEnd ];
      timeDomainStart = +value[0], timeDomainEnd = +value[1];
      return gantt;
   };

   gantt.timeDomainMode = function(value) {
      if (!arguments.length)
         return timeDomainMode;
      timeDomainMode = value;
      return gantt;

   };

   gantt.taskTypes = function(value) {
      if (!arguments.length)
         return taskTypes;
      taskTypes = value;
      return gantt;
   };

   gantt.taskStatus = function(value) {
      if (!arguments.length)
         return taskStatus;
      taskStatus = value;
      return gantt;
   };

   gantt.width = function(value) {
      if (!arguments.length)
         return width;
      width = +value;
      return gantt;
   };

   gantt.height = function(value) {
      if (!arguments.length)
         return height;
      height = +value;
      return gantt;
   };

   gantt.tickFormat = function(value) {
      if (!arguments.length)
         return tickFormat;
      tickFormat = value;
      return gantt;
   };

   return gantt;
};