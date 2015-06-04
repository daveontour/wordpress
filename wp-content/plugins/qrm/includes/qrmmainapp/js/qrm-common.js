QRM = {};


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
    var qOK = false;

    if (mat.probVal1 != null && 0 <= prob && prob <= mat.probVal1 && mat.maxProb >= 1) {
        qprob = 1.0 + (prob / mat.probVal1);
        qOK = true;
    }
    if (mat.probVal1 != null && mat.probVal2 != null && mat.probVal1 < prob && prob <= mat.probVal2 && mat.maxProb >= 2) {
        qprob = 2.0 + ((prob - mat.probVal1) / (mat.probVal2 - mat.probVal1));
        qOK = true;
    }
    if (mat.probVal2 != null && mat.probVal3 != null && mat.probVal2 < prob && prob <= mat.probVal3 && mat.maxProb >= 3) {
        qprob = 3.0 + ((prob - mat.probVal2) / (mat.probVal3 - mat.probVal2));
        qOK = true;
    }
    if (mat.probVal3 != null && mat.probVal4 != null && mat.probVal3 < prob && prob <= mat.probVal4 && mat.maxProb >= 4) {
        qprob = 4.0 + ((prob - mat.probVal3) / (mat.probVal4 - mat.probVal3));
        qOK = true;
    }
    if (mat.probVal4 != null && mat.probVal5 != null && mat.probVal4 < prob && prob <= mat.probVal5 && mat.maxProb >= 5) {
        qprob = 5.0 + ((prob - mat.probVal4) / (mat.probVal5 - mat.probVal4));
        qOK = true;
    }
    if (mat.probVal5 != null && mat.probVal6 != null && mat.probVal5 < prob && prob <= mat.probVal6 && mat.maxProb >= 6) {
        qprob = 6.0 + ((prob - mat.probVal5) / (mat.probVal6 - mat.probVal5));
        qOK = true;
    }
    if (mat.probVal6 != null && mat.probVal7 != null && mat.probVal6 < prob && prob <= mat.probVal7 && mat.maxProb >= 7) {
        qprob = 7.0 + ((prob - mat.probVal6) / (mat.probVal7 - mat.probVal6));
        qOK = true;
    }
    if (mat.probVal7 != null && mat.probVal8 != null && mat.probVal7 < prob && prob <= mat.probVal8 && mat.maxProb == 8) {
        qprob = 8.0 + ((prob - mat.probVal7) / (mat.probVal8 - mat.probVal7));
        qOK = true;
    }

    if (!qOK) {
        qprob = mat.maxProb + 0.999;
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

function setConfigMatrix(tolString, maxImpact, maxProb, svgDivID, matrixChangeCB) {

    var margin = {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0
    };
    var width = 220 - margin.left - margin.right;
    var height = 220 - margin.top - margin.bottom;

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
            d.id = d.impact + "-" + d.prob;
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
        .attr("qrmID", function (d) {
            return String(d.impact) + String(d.prob);
        })
        .attr("class", function (d) {
            return "tolc" + d.tol;
        })
        .attr("width", gridSizeX)
        .attr("height", gridSizeY)
        .on("click", function (d) {

            var e = $(this);
            if (hasClassSVG(e, "tolc5", "tolc1")) {
                matrixChangeCB();
                return;
            }
            if (hasClassSVG(e, "tolc4", "tolc5")) {
                matrixChangeCB();
                return;
            }
            if (hasClassSVG(e, "tolc3", "tolc4")) {
                matrixChangeCB();
                return;
            }
            if (hasClassSVG(e, "tolc2", "tolc3")) {
                matrixChangeCB();
                return;
            }
            if (hasClassSVG(e, "tolc1", "tolc2")) {
                matrixChangeCB();
                return;
            }
            if (hasClassSVG(e, "tolc", "tolc5")) {
                matrixChangeCB();
                return;
            }

            matrixChangeCB();


        });

    svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 1) + 12] + ")")
        .text("P1");

    svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 2) + 12] + ")")
        .text("P2");
    if (maxProb > 2) svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 3) + 12] + ")")
        .text("P3");

    if (maxProb > 3) svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 4) + 12] + ")")
        .text("P4");
    if (maxProb > 4) svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 5) + 12] + ")")
        .text("P5");
    if (maxProb > 5) svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 6) + 12] + ")")
        .text("P6");
    if (maxProb > 6) svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 7) + 12] + ")")
        .text("P7");
    if (maxProb > 7) svg.append("text")
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .style("font-weight", "normal")
        .attr("transform", "translate(" + [8, gridSizeY * (maxProb - 8) + 12] + ")")
        .text("P8");
}

function hasClassSVG(tmp, oldClass, newClass) {

    //Templated the jQuery hasClass function to work for SVG

    var rclass = /[\t\r\n\f]/g;
    var className = " " + oldClass + " ",
        i = 0,
        l = tmp.length;
    for (; i < l; i++) {
        if (tmp[i].nodeType === 1 && (" " + tmp[i].className.baseVal + " ").replace(rclass, " ").indexOf(className) >= 0) {
            tmp[i].className.baseVal = newClass;
            return true;
        }
    }
    return false;
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
        //        top: radius * 2,
        //        right: radius * 2,
        //        bottom: radius * 2,
        //        left: radius * 2
        top: radius,
        right: radius,
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
            "rect.tolNoHover5 {fill: #ed5565;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover4 {fill: #f8ac59;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover3 {fill: #ffff55;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover2 {fill: #1ab394;stroke: #E6E6E6;stroke-width: 2px; }" +
            "rect.tolNoHover1 {fill: #1c84c6; stroke: #E6E6E6; stroke-width: 2px; }" +
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

function minimiseSideBar(state) {


    if (!jQuery("body").hasClass("mini-navbar")) {
        jQuery("body").addClass("mini-navbar");
    }

    if (!jQuery('body').hasClass('mini-navbar') || jQuery('body').hasClass('body-small')) {
        // Hide menu in order to smoothly turn on when maximize menu
        jQuery('#side-menu').hide();
        // For smoothly turn on menu
        setTimeout(
            function () {
                jQuery('#side-menu').fadeIn(500);
            }, 100);
    } else if ($('body').hasClass('fixed-sidebar')) {
        jQuery('#side-menu').hide();
        setTimeout(
            function () {
                jQuery('#side-menu').fadeIn(500);
            }, 300);
    } else {
        // Remove all inline style from jquery fadeIn function to reset menu state
        jQuery('#side-menu').removeAttr('style');
    }

    //Resize things that may have be impacted
    setTimeout(
        function () {
            jQuery(window).trigger('resize');
        }, 250);
}

d3.gantt = function (calController) {
    var FIT_TIME_DOMAIN_MODE = "fit";

    var margin = {
        top: 30,
        right: 40,
        bottom: 20,
        left: 100
    };
    var timeDomainStart = d3.time.day.offset(new Date(), -3);
    var timeDomainEnd = d3.time.hour.offset(new Date(), +3);
    var timeDomainMode = FIT_TIME_DOMAIN_MODE; // fixed or fit
    var taskTypes = [];
    var taskStatus = [];

    var height = null;
    var width = null;
    var tickFormat = "%b %Y";

    var keyFunction = function (d) {
        return d.startDate + d.taskName + d.endDate;
    };
    var rectTransform = function (d) {
        return "translate(" + x(d.startDate) + "," + y(d.taskName) + ")";
    };

    var x = d3.time.scale().domain([timeDomainStart, timeDomainEnd]).range([0, width]).clamp(true);
    var y = d3.scale.ordinal().domain(taskTypes).rangeRoundBands([0, height - margin.top - margin.bottom], .1);

    var xAxis = d3.svg.axis().scale(x).orient("bottom")
        .tickFormat(d3.time.format(tickFormat))
        .tickSubdivide(true).tickSize(8).tickPadding(8);

    var yAxis = d3.svg.axis().scale(y).orient("left").tickSize(0);

    var initTimeDomain = function (tasks) {
        if (timeDomainMode === FIT_TIME_DOMAIN_MODE) {
            if (tasks === undefined || tasks.length < 1) {
                timeDomainStart = d3.time.day.offset(new Date(), -3);
                timeDomainEnd = d3.time.hour.offset(new Date(), +3);
                return;
            }
            tasks.sort(function (a, b) {
                return a.endDate - b.endDate;
            });
            timeDomainEnd = tasks[tasks.length - 1].endDate;

            tasks.sort(function (a, b) {
                return a.startDate - b.startDate;
            });
            timeDomainStart = tasks[0].startDate;
        }
    };

    var initAxis = function () {
        x = d3.time.scale().domain([timeDomainStart, timeDomainEnd]).range([0, width]).clamp(true);
        y = d3.scale.ordinal().domain(taskTypes).rangeRoundBands([0, height - margin.top - margin.bottom], .1);
        xAxis = d3.svg.axis().scale(x).orient("bottom").tickFormat(d3.time.format(tickFormat)).tickSubdivide(true).tickSize(8).tickPadding(8);
        yAxis = d3.svg.axis().scale(y).orient("left").tickSize(0);
    };


    function gantt(tasks, svgdiv, w, h) {

        height = h - margin.top - margin.bottom - 5;
        width = w - margin.right - margin.left - 5;

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
            .attr("class", function (d) {
                return d.className;
            })
            .attr("y", 0)
            .attr("transform", rectTransform)
            .attr("height", function (d) {
                return y.rangeBand();
            })
            .attr("width", function (d) {
                return (x(d.endDate) - x(d.startDate));
            })
            .on('mouseover', function (d) {
                try {
                    calController.toolTip(d);
                } catch (e) {
                    alert(e.message);
                }
            })
            .on("mouseout", function () {
                try {
                    calController.toolTip(false);
                } catch (e) {
                    alert(e.message);
                }
            })
            .on("click", function (d) {
                calController.edirRisk(d.riskID);
            });

        svg.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0, " + (height - margin.top - margin.bottom) + ")")
            .transition()
            .call(xAxis);

        svg.append("text")
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [width / 2, 0] + ")")
            .text(calController.project.title);



        svg.append("g").attr("class", "y axis").transition().call(yAxis);

        return gantt;

    }

    gantt.redraw = function (tasks) {

        initTimeDomain(tasks);
        initAxis();

        var svg = d3.select("svg");

        var ganttChartGroup = svg.select(".gantt-chart");
        var rect = ganttChartGroup.selectAll("rect").data(tasks, keyFunction);

        rect.enter()
            .insert("rect", ":first-child")
            .attr("rx", 5)
            .attr("ry", 5)
            .attr("class", "bar")
            .transition()
            .attr("y", 0)
            .attr("transform", rectTransform)
            .attr("height", function (d) {
                return y.rangeBand();
            })
            .attr("width", function (d) {
                return (x(d.endDate) - x(d.startDate));
            });

        rect.transition()
            .attr("transform", rectTransform)
            .attr("height", function (d) {
                return y.rangeBand();
            })
            .attr("width", function (d) {
                return (x(d.endDate) - x(d.startDate));
            });

        rect.exit().remove();

        svg.select(".x").transition().call(xAxis);
        svg.select(".y").transition().call(yAxis);

        return gantt;
    };

    gantt.margin = function (value) {
        if (!arguments.length)
            return margin;
        margin = value;
        return gantt;
    };

    gantt.timeDomain = function (value) {
        if (!arguments.length)
            return [timeDomainStart, timeDomainEnd];
        timeDomainStart = +value[0], timeDomainEnd = +value[1];
        return gantt;
    };

    gantt.timeDomainMode = function (value) {
        if (!arguments.length)
            return timeDomainMode;
        timeDomainMode = value;
        return gantt;

    };

    gantt.taskTypes = function (value) {
        if (!arguments.length)
            return taskTypes;
        taskTypes = value;
        return gantt;
    };

    gantt.taskStatus = function (value) {
        if (!arguments.length)
            return taskStatus;
        taskStatus = value;
        return gantt;
    };

    gantt.width = function (value) {
        if (!arguments.length)
            return width;
        width = +value;
        return gantt;
    };

    gantt.height = function (value) {
        if (!arguments.length)
            return height;
        height = +value;
        return gantt;
    };

    gantt.tickFormat = function (value) {
        if (!arguments.length)
            return tickFormat;
        tickFormat = value;
        return gantt;
    };

    return gantt;
};

function SorterLayout(rankCtl, $scope) {

    this.height = 500;
    this.width = 500;
    this.itemWidth = 500;
    this.items = null;
    this.transMatrix = [1, 0, 0, 1, 20, 30];
    this.svgDiv = null;
    this.itemHeight = 25;
    this.minItemHeight = 20;
    this.minItemWidth = 120;
    this.topSVG = null;
    this.svgDiv = null;
    this.numItemsPerColumn = 0;
    this.numColumns = 0;
    this.lastColumn = 0;
    this.lastRow = 0;
    this.minScaleFactor = 0.75;

    var layout = this;

    this.drag = d3.behavior.drag()
        .on("dragstart", function (d) {

            var e = d3.event.sourceEvent;
            if (e.ctrlKey) return;

            var x = d.tx + 5;
            var y = d.ty + 5;

            d3.select("#rankGroupHolder").append("use")
                .attr("xlink:href", "#ghostDef")
                .attr("tx", x)
                .attr("ty", y)
                .attr("totTy", d.totTy)
                .attr("transform", "translate(" + x + ", " + y + ")")
                .attr("id", "sorterGhost");

            d3.select("#rankGroupHolder").append("use")
                .attr("xlink:href", "#insertionMarkerDef")
                .attr("ty", y)
                .attr("tx", x)
                .attr("transform", "translate(" + x + ", " + y + ")")
                .attr("id", "insertionMarker")
                .style("display", "none");

            this.parentNode.appendChild(this);
        })
        .on("drag", function () {

            layout.notifyDirtyListeners();
            this.parentNode.appendChild(this);

            var yOffset = Math.floor(d3.event.y / layout.itemHeight) * layout.itemHeight;

            //Left boundary limit
            var posnX = Math.max(d3.event.x, 0);

            // Right boundary limit
            posnX = Math.min(posnX, (layout.lastColumn) * layout.itemWidth);

            // Top boundary limit
            var posnY = Math.max(d3.event.y, -layout.itemHeight);

            //Bottom boundary limit
            posnY = Math.min(posnY, layout.itemHeight * layout.numItemsPerColumn);

            d3.select(this).attr("transform", function (d, i) {
                return "translate(" + [posnX, posnY] + ")";
            });

            var y = yOffset + (layout.itemHeight - 7);
            y = Math.min(y, layout.itemHeight * layout.numItemsPerColumn - 7);

            var col = (Math.floor(posnX / layout.itemWidth));
            var x = col * layout.itemWidth;

            //Last column limit
            if (col == layout.lastColumn) {
                y = Math.min(y, layout.itemHeight * layout.lastRow - 7);
            }

            //Keep it withing the lower limit for the marker.
            y = Math.max(y, -7);

            var totTy = col * layout.numItemsPerColumn * layout.itemHeight + y;

            d3.select("#insertionMarker")
                .attr("transform", function (d, i) {
                    return "translate(" + [x, y] + ")";
                })
                .attr("totTy", totTy)
                .attr("ty", y)
                .attr("tx", x)
                .style("display", "inline");

        })
        .on("dragend", function (d) {


            var ghost = d3.select("#sorterGhost");
            var marker = d3.select("#insertionMarker");

            var ghostTotY = Number(ghost.attr("totTy"));
            var markerTotY = Number(marker.attr("totTy"));

            if (ghostTotY == markerTotY || ghostTotY == markerTotY + layout.itemHeight) {
                var node = d3.select(this);
                node.attr("transform", function () {
                    return "translate(" + [Number(node.attr("tx")), Number(node.attr("ty"))] + ")";
                });
                ghost.remove();
                marker.remove();
                return;
            }

            var moveUp = ghostTotY > markerTotY;

            var nodes = d3.select("#rankGroupHolder").selectAll("g.risk");

            if (moveUp) {
                nodes.filter(function (d2, i) {
                    return (d2.totTy < ghostTotY && d2.totTy > markerTotY);
                }).each(function (d2, i) {
                    layout.calcNewPosition(d2, moveUp);
                    layout.repositionNode(this, d2, moveUp);
                });
            } else {
                nodes.filter(function (d2, i) {
                    return (d2.totTy > ghostTotY && d2.totTy <= markerTotY);
                }).each(function (d2, i) {
                    layout.calcNewPosition(d2, moveUp);
                    layout.repositionNode(this, d2, moveUp);
                });
            }

            layout.positionDropNode(this, d, marker, moveUp);

            ghost.remove();
            marker.remove();
        })
        .origin(function (d) {
            var bbox = this.getBBox();
            var ctm = this.getCTM();
            return {
                x: bbox.x + ctm.e / ctm.a - layout.transMatrix[4] / layout.transMatrix[0],
                y: bbox.y + ctm.f / ctm.d - layout.transMatrix[5] / layout.transMatrix[3]
            };
        });

    this.setHeight = function (h) {
        this.height = h;
    };

    this.setWidth = function (w) {
        this.width = w;
    };

    this.setItems = function (i) {
        this.items = i;
    };

    this.sortItems = function () {
        this.items.sort(function (a, b) {
            var v = 0;
            if (a.rank && b.rank) {
                return Number(a.rank) - Number(b.rank);
            } else {
                return Number(b.currentTolerance) - (a.currentTolerance);
            }
        });
    };

    this.scale = function (x, y) {
        this.transMatrix[0] = x;
        this.transMatrix[3] = y;
    };

    this.translate = function (x, y) {
        this.transMatrix[4] = x;
        this.transMatrix[5] = y;
    };

    this.normaliseRanks = function () {
        this.items.sort(function (a, b) {
            if (a.totTy != null && b.totTy != null) {
                return a.totTy - b.totTy;
            } else {
                return a.rank - b.rank;
            }
        });

        var rank = 0;
        this.items.forEach(function (item) {
            item.rank = Number(rank++);
        });

    };

    this.notifyDirtyListeners = function () {
            if (this.dirtyListener != null) {
                this.dirtyListener();
            }
        },
        this.setDirtyListener = function (fn) {
            this.dirtyListener = fn;
        },
        this.setSVGDiv = function (div) {
            this.svgDiv = "#" + div;
        };

    this.setItemHeight = function (h) {
        this.itemHeight = Math.max(h, this.minItemHeight);
    };

    this.setItemWidth = function (w) {
        this.itemWidth = Math.max(w, this.minItemWidth);
    };

    this.positionDropNode = function (element, data, marker, moveUp) {

        var dropNode = d3.select(element);
        var markerTotY = Number(marker.attr("totTy"));

        data.totTy = markerTotY;

        data.tx = Number(marker.attr("tx"));
        data.ty = Number(marker.attr("ty")) + 7;

        if (moveUp) {
            data.totTy = data.totTy + this.itemHeight;

            if (data.ty > (layout.numItemsPerColumn - 1) * layout.itemHeight) {
                data.ty = 0;
                data.tx = data.tx + layout.itemWidth;
            }

        } else {
            data.ty = data.ty - this.itemHeight;

            if (data.ty < 0) {
                data.ty = (layout.numItemsPerColumn - 1) * layout.itemHeight;
                data.tx = data.tx - layout.itemWidth;
            }
        }
        dropNode.transition().attr("transform", "translate(" + [data.tx, data.ty] + ")");
    };

    this.calcNewPosition = function (item, moveUp) {

        if (moveUp) {
            item.totTy = item.totTy + layout.itemHeight;
            item.ty = item.ty + layout.itemHeight;

            if (item.ty > (layout.numItemsPerColumn - 1) * layout.itemHeight) {
                item.ty = 0;
                item.tx = item.tx + layout.itemWidth;
            }
        } else {
            item.totTy = item.totTy - layout.itemHeight;
            item.ty = item.ty - layout.itemHeight;

            if (item.ty < 0) {
                item.ty = (layout.numItemsPerColumn - 1) * layout.itemHeight;
                item.tx = item.tx - layout.itemWidth;
            }
        }


    };

    this.repositionNode = function (element, data, moveUp) {
        d3.select(element).transition().attr("transform", function () {
            return "translate(" + [data.tx, data.ty] + ")";
        });
    };

    this.preLayout = function () {

        var numItems = this.items.length;
        var totalItemHeight = (numItems + 1.5) * this.itemHeight;

        var colNum = 1;
        var scaleFactor = 0;

        this.sortItems();

        do {
            var columnHeight = totalItemHeight / colNum;
            scaleFactor = this.height / columnHeight;
            colNum++;
        } while (scaleFactor < this.minScaleFactor)

        this.scale(1, Math.min(1, scaleFactor));

        //Scale to make full use of height and add some margin at the botom

        this.layoutHeight = this.height / this.transMatrix[3] - 1.5 * this.itemHeight / this.transMatrix[3];
        this.numItemsPerColumn = Math.floor(this.layoutHeight / this.itemHeight);

        colNum = Math.ceil(numItems / this.numItemsPerColumn);
        this.setItemWidth((this.width - 10 - this.transMatrix[4]) / (colNum));
    };

    this.layoutTable = function () {

        this.preLayout();

        d3.select(this.svgDiv + " svg").remove();

        this.topSVG = d3.select(this.svgDiv).append("svg")
            .attr("width", this.width)
            .attr("height", this.height);

        this.createDefinitions();

        this.topSVG.append("text")
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [this.width / 2, 20] + ")")
            .text(rankCtl.project.title);

        this.svg = this.topSVG
            .append("g")
            .attr("id", "rankGroupHolder")
            .attr("h", this.height)
            .attr("transform", "matrix(" + this.transMatrix.join(' ') + ")");

        var me = this;
        var rowNum = 0;
        var colNum = 0;
        this.items.forEach(function (item) {

            item.ty = rowNum * me.itemHeight;
            item.tx = colNum * me.itemWidth;
            item.totTy = item.ty + colNum * me.itemHeight * me.numItemsPerColumn;

            rowNum++;
            if (rowNum == me.numItemsPerColumn) {
                colNum++;
                rowNum = 0;
            }
        });

        this.lastColumn = colNum;
        this.lastRow = rowNum;

        var nodes = this.svg.selectAll("g.state").data(this.items);

        var risk = nodes.enter()
            .append("g")
            .attr("class", "risk")
            .attr("id", function (d) {
                return d.riskCode;
            })
            .attr("tx", function (d) {
                return d.tx;
            })
            .attr("colY", function (d) {
                return d.colY;
            })
            .attr("transform", function (d, i) {
                return "translate(" + d.tx + ", " + d.ty + ")";
            })
            .attr("ty", function (d, i) {
                return d.ty;
            })
            .on("mouseover", function (d) {
                rankCtl.showDesc = true;
                rankCtl.showInstructions = false;
                rankCtl.displayRisk = d;
                $scope.$apply();
            })
            .on("mouseout", function (d) {
                rankCtl.showDesc = false;
                $scope.$apply();
            })
            .on("click", function (d) {
                var e = d3.event;
                if (!e.ctrlKey) return;
                if (d3.event.defaultPrevented) return;
                if (rankCtl.dirty) {
                    msg("Open Risk", "Please save or cancel existing changes before opening the risk");
                } else {
                    rankCtl.editRisk(d.id);
                }
            })
            .call(this.drag);

        risk.append("rect")
            .attr("width", this.itemWidth - 10)
            .attr("height", this.itemHeight - 5)
            .attr("fill", 'aliceblue')
            .style("stroke", 'gray')
            .attr("class", 'tolText')
            .on("mouseover", function (d) {
                rankCtl.showDesc = true;
                rankCtl.showInstructions = false;
                rankCtl.displayRisk = d;
                $scope.$apply();
            })
            .on("mouseout", function (d) {
                rankCtl.showDesc = false;
                $scope.$apply();
            })
            .attr("transform", "translate(5,5)");

        risk.append("rect")
            .attr("width", "100")
            .attr("height", this.itemHeight - 8)
            .attr("class", function (d) {
                var tol = null;
                if (d.treated) {
                    tol = d.treatedTolerance;
                } else {
                    tol = d.inherentTolerance;
                }
                return "tol" + tol;
            })
            .style("stroke", 'gray')
            .on("mouseover", function (d) {
                rankCtl.showInstructions = false;
                rankCtl.showDesc = true;
                rankCtl.displayRisk = d;
                $scope.$apply();
            })
            .on("mouseout", function (d) {
                rankCtl.showDesc = false;
                $scope.$apply();
            })

        .attr("transform", "translate(7,7)");

        risk.append("text")
            .style("pointer-events", "none")
            .attr("transform", "translate(10,25)")
            .text(function (d) {
                return d.riskProjectCode;
            });

        var textGroup = risk.append("g")
            .attr("transform", "translate(110,25)");

        textGroup.append("clipPath")
            .attr("id", function (d) {
                return "clipPath" + d.riskProjectCode;
            })
            .append("rect")
            .attr("transform", "translate(0,-20)")
            .attr("width", this.itemWidth - 120)
            .attr("height", this.itemHeight);

        textGroup.append("text")
            .style("pointer-events", "none")
            .attr("clip-path", function (d) {
                return "url(#clipPath" + d.riskProjectCode + ")"
            })
            .text(function (d) {
                return d.title;
            });

    };

    this.createDefinitions = function () {
        var def = this.topSVG.append("defs");

        def.append("style")
            .attr("type", "text/css")
            .text(
                ".compass{fill: #fff; stroke: #000;stroke-width:   1.5; }" +
                ".button{ fill: #225EA8; stroke: #0C2C84; stroke-miterlimit: 6; stroke-linecap:  round; }" +
                ".button:hover{ stroke-width: 2; }" +
                ".plus-minus{ fill: #fff; pointer-events: none; }" +
                "g.risk {cursor:move }" +
                ".marker {stroke-width: 4px; stroke-dasharray: 4px; stroke:red}" +
                ".markervert {stroke-width: 4px; stroke:red}" +
                "rect.subRankText {fill: aliceblue ;stroke: gray;}" +
                "rect.subRankText:hover {fill: #157fcc ;stroke: gray;}" +
                "rect.tol5 {fill: #ed5565;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tol4 {fill: #f8ac59;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tol3 {fill: #ffff55;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tol2 {fill: #1ab394;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tol1 {fill: #1c84c6; stroke: #E6E6E6; stroke-width: 2px; }" +
                "g.risk text { font: 12px sans-serif; font-weight : normal; pointer-events : none; }" +
                "g.state text.treated { fill:blue; font: 12px sans-serif; font-weight : bold; pointer-events : none; }");

        // Create the insertion marker definition
        var marker = def.append("g").attr("id", "insertionMarkerDef");

        marker.append("line")
            .attr("x1", "1")
            .attr("y1", "0")
            .attr("x2", "1")
            .attr("y2", "19")
            .attr("class", "markervert");

        marker.append("line")
            .attr("x1", this.itemWidth - 4)
            .attr("y1", "0")
            .attr("x2", this.itemWidth - 4)
            .attr("y2", "19")
            .attr("class", "markervert");

        marker.append("line")
            .attr("x1", "1")
            .attr("y1", "9")
            .attr("x2", this.itemWidth - 4)
            .attr("y2", "9")
            .attr("class", "marker");

        //Create the ghost holder definition
        def.append("g").attr("id", "ghostDef").append("rect")
            .attr("width", this.itemWidth - 10)
            .attr("height", this.itemHeight - 5)
            .style("stroke", 'gray')
            .style("fill", 'transparent')
            .style("stroke-dasharray", '4px');

        def.append("clipPath")
            .attr("id", "rankTextClipPath")
            .append("rect")
            .attr("width", this.itemWidth - 10)
            .attr("height", this.itemHeight - 5)
            .attr("transform", "translate(5,5)");

    };
}

tooltipProb = d3.select("body")
    .append("div")
    .style("position", "absolute")
    .style("background-color", "rgba(255, 255, 255, 0.5)")
    .style("z-index", "10")
    .style("padding", "10")
    .style("border-style", "solid")
    .style("border-radius", "5px")
    .style("border-width", "1px")
    .style("border-color", "black")
    .style("font-size", "18px")
    .style("font-weight", "normal")
    .style("visibility", "hidden")
    .text("a simple tooltip");

function parentSort(projArr) {

    projArr.forEach(function (e) {
        e.$$treeLevel = -100;
    });

    var sortedArray = $.grep(projArr, function (value) {
        return value.parent_id <= 0;
    })

    sortedArray.forEach(function (e) {
        e.$$treeLevel = 0;
    });

    projArr = $.grep(projArr, function (value) {
        return value.$$treeLevel < 0
    });

    while (projArr.length > 0) {

        for (j = 0; j < projArr.length; j++) {

            var child = projArr[j];
            var found = false;
            for (var i = 0; i < sortedArray.length; i++) {

                var parent = sortedArray[i];

                if (child.parent_id == parent.id) {
                    child.$$treeLevel = parent.$$treeLevel + 1;
                    sortedArray.splice(i + 1, 0, child);
                    found = true;
                    break;
                }
            }
            if (found) break;
        }
        projArr = $.grep(projArr, function (value) {
            return value.$$treeLevel < 0
        });
    }

    return sortedArray;

}

function objectiveSort(objArr) {

    objArr.forEach(function (e) {
        e.$$treeLevel = -100;
    });

    var sortedArray = $.grep(objArr, function (value) {
        // Find if the parent objective is in the array, if not, return as a top level
        var tmp = $.grep(objArr, function (p) {
            return p.id == value.parentID;
        })
        return tmp.length == 0;
    })

    sortedArray.forEach(function (e) {
        e.$$treeLevel = 0;
    });

    objArr = $.grep(objArr, function (value) {
        return value.$$treeLevel < 0
    });

    while (objArr.length > 0) {

        for (j = 0; j < objArr.length; j++) {

            var child = objArr[j];
            var found = false;
            for (var i = 0; i < sortedArray.length; i++) {

                var parent = sortedArray[i];

                if (child.parentID == parent.id) {
                    child.$$treeLevel = parent.$$treeLevel + 1;
                    sortedArray.splice(i + 1, 0, child);
                    found = true;
                    break;
                }
            }
            if (found) break;
        }
        objArr = $.grep(objArr, function (value) {
            return value.$$treeLevel < 0
        });
    }

    return sortedArray;


}

function getProjectParents(projMap, projectID) {
    var parentID = projMap.get(projectID).parent_id;

    retn = new Array();
    if (projMap.findIt(parentID) > -1) {
        var tmp = projMap.get(parentID);
        retn.push(tmp);
        return retn.concat(getProjectParents(projMap, parentID));
    } else {
        return retn;
    }
}

function getLinearObjectives(projMap, projectID) {

    var obj = new Array();
    var proj = projMap.get(projectID);

    obj = obj.concat(proj.objectives);

    getProjectParents(projMap, projectID).forEach(function (p) {
        if (p.id != projectID) {
            obj = obj.concat(p.objectives);
        }
    });

    return obj;
}

function getFamilyCats(projMap, projectID) {

    var parents = getProjectParents(projMap, projectID);

    var cat = new Array();
    var proj = projMap.get(projectID);

    cat = cat.concat(proj.categories);

    getProjectParents(projMap, projectID).forEach(function (p) {
        if (p.id != projectID) {
            cat = cat.concat(p.categories);
        }
    });

    return cat;

}