// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class for handling question issue page.
 *
 * @module     qtype_stack/threedradio
 * @package    qtype_stack
 * @author     David Rise Knotten <david_knotten@hotmail.no>
 * @copyright  2021 NTNU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import marchingCubes from "./marchingcube";

// TODO refactor the code to allow the setup of ranges work with implicit graphs too,
// as now an implicit graph has to hook into an existing canvas
// for implicit, one option could be te iterate through the marchingcubes result and find
// the largest/smallest values of z

// TODO creategraphcolors function for implicit graphs (If possible)

// Bruke {...stdvals, ...opts} men createzrange vil alti bli overwrita

/**
 *
 * @type {{"backgroundcolor": string, ranges: string, graphcolorstyle: string, disableaxes: boolean}}
 */
const standardValues = {
    'backgroundcolor': 'white',
    'graphcolorstyle': 'lightblue',
    'disableaxes': false,
    'ranges': '-3;3;-3;3',
    'resolution': 64,
    'graphopacity': null,
    'canvaswidth': '80%',
    'canvasheight': null,
};

/**
 *
 * @param inputid The ID of the input sibling-node where the canvas will be placed
 * @param exp The code element containing the expression
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
export const threedradio = (inputid, exp, options) => {

    options = {
        ...standardValues,
        ...options,
    }

    if (exp == null || exp == '') return null
    // Remove code tags
    exp = exp.slice(6, exp.length-7);

    // Get the option node where the input and canvas are placed
    let element = document.getElementById(inputid).parentNode;
    element.style.width = options['canvaswidth'] ?? '80%';
    element.style.height = options['canvasheight'] ?? 0;
    // If element height is 0, make the padding 50% of the parent elements height
    if (element.style.height == '0px') {
        element.style.paddingBottom = '50%';
    }
    element.style.margin = '10px';
    element.style.display = 'inline-flex';

    const waitfordependencies = () => {
        if (typeof mathBox == "undefined" || typeof Parser == "undefined") {
            setTimeout(waitfordependencies, 100);
        } else {
            createCanvas(element, exp, options)
        }
    }
    waitfordependencies();
}

const createMathBox = (element, expression, options = {}) => {
    options = {
        ...standardValues,
        ...options,
    }
    console.log(element)
    let canvasDiv = document.createElement('div')
    canvasDiv.style.width = options['canvaswidth'] ?? '80%';
    canvasDiv.style.height = options['canvasheight'] ?? '0';
    canvasDiv.style.paddingBottom = '50%';
    canvasDiv.style.margin = '10px';
    canvasDiv.style.display = 'inline-flex';
    element.appendChild(canvasDiv)
    return createCanvas(canvasDiv, expression, options)
}

/**
 * Creates a MathBox canvas under the element, the expression and options are optional and can be nulled
 *
 * @param element The element the mathbox canvas should be placed
 * @param {String|null} expression The mathmatical expression to render
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
export const createCanvas = (element, expression, options = {}) => {
    // Setup
    //==================================================================================================================
    // THe nxn resolution of the render
    options = {
        ...standardValues,
        ...options,
    }
    var resolution = options.resolution;
    let disableaxes = options.disableaxes;
    let ranges = options.ranges.split(';').map(num => parseFloat(num));

    var	xMin = parseFloat(ranges[0]), xMax = parseFloat(ranges[1]), yMin = parseFloat(ranges[2]), yMax = parseFloat(ranges[3]), zMin = -3, zMax = 3;
    if( ranges.length > 4) {
        zMin = parseFloat(ranges[4])
        zMax = parseFloat(ranges[5])
    }
    //==================================================================================================================

    var mathbox = mathBox({
        plugins: ['core', 'controls', 'cursor', 'mathbox'],
        controls: {klass: THREE.OrbitControls},
        element: element
    });
    if (mathbox.fallback) throw "WebGL not supported"

    var three = mathbox.three;
    three.renderer.setClearColor(new THREE.Color(0xFFFFFF), 1.0);

    // save as variable to adjust later
    var view = mathbox.cartesian({
            range: [[xMin, xMax], [yMin, yMax], [zMin,zMax]],
            scale: [2,2,2],
    });

    if (expression != null && expression != '') {
        let zFunc = Parser.parse(expression).toJSFunction(['x','y']);
        /*
        if ( ranges.length <= 4) {
            [zMin, zMax] = calculateZRange(zFunc, ranges, resolution).map(num => parseFloat(num))
        }
         */

        [zMin, zMax] = createGraph(expression, view, options);
    }


    // Sets the ranges of the render
    view.set("range", [[xMin, xMax], [zMin,zMax],[yMin, yMax]]);

    // setting proxy:true allows interactive controls to override base position
    var camera = mathbox.camera( { proxy: true, position: [4,2,4] } );
    // Display axes unless they are disabled
    if (!disableaxes) {
        var xAxis = view.axis( {axis: 1, width: 8, zIndex:1, detail: 40, color:"red"} );
        var xScale = view.scale( {axis: 1, divide: 10, nice:true, zero:true} );
        var xTicks = view.ticks( {width: 5, size: 15, color: "red", zBias:2} );
        var xFormat = view.format( {digits: 2, font:"Arial", weight: "bold", style: "normal", source: xScale} );
        var xTicksLabel = view.label( {color: "red", zIndex: 1, offset:[0,-20], points: xScale, text: xFormat} );

        var yAxis = view.axis( {axis: 3, width: 8, detail: 40, color:"green"} );
        var yScale = view.scale( {axis: 3, divide: 5, nice:true, zero:false} );
        var yTicks = view.ticks( {width: 5, size: 15, color: "green", zBias:2} );
        var yFormat = view.format( {digits: 2, font:"Arial", weight: "bold", style: "normal", source: yScale} );
        var yTicksLabel = view.label( {color: "green", zIndex: 1, offset:[0,0], points: yScale, text: yFormat} );

        var zAxis = view.axis( {axis: 2, width: 8, detail: 40, color:"blue"} );
        var zScale = view.scale( {axis: 2, divide: 5, nice:true, zero:false} );
        var zTicks = view.ticks( {width: 5, size: 15, color: "blue", zBias:2} );
        var zFormat = view.format( {digits: 2, font:"Arial", weight: "bold", style: "normal", source: zScale} );
        var zTicksLabel = view.label( {color: "blue", zIndex: 1, offset:[0,0], points: zScale, text: zFormat} );

    }
    view.grid( {axes:[1,3], width: 2, divideX: 20, divideY: 20, opacity:0.25} );


    if(options.hasOwnProperty('backgroundcolor')) {
        mathbox.three.renderer.setClearColor(options['backgroundcolor']);
    }
    return [mathbox, view];
}

/**
 * Creates a graph from the input string
 * If the string contains '=' or 'z' it will be assumed it is an implicit function
 *
 * @param {String} exp The equation/function as a string
 * @param {Object} view The mathbox cartesian to add the graph to
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
const createGraph = (exp, view, options = {}) => {
    if ((''+exp).includes('=') || (''+exp).includes('z')) {
        console.log('Implicit graph');
        return createImplicitGraph(exp,view,options);
    } else if ((''+exp).includes('u') || (''+exp).includes('v')) {
        console.log('Parametric graph')
        let [x, y, z] = (''+exp).split(',')
        return createParametricGraph(x, y, z, view, options)
    } else {
        console.log('Explicit graph');
        return createExplicitGraph(exp, view, options);
    }
}

/**
 * Calculates the minima and maxima of Z
 *
 * @param zFunc function to evaluate
 * @param {[number]} ranges array containing the pre-defined x and y ranges [xmin, xmax, ymin, ymax]
 * @param {number} resolution the graph resolution
 * @return {[number, number]} the minima and maxima of z
 */
const calculateZRange = (zFunc, ranges, resolution) => {
    let [xMin, xMax, yMin, yMax] = ranges.map(num => parseFloat(num))
    var xStep = (xMax - xMin) / resolution;
    var yStep = (yMax - yMin) / resolution;
    var zSmallest = zFunc(xMin, xMax);
    var zBiggest  = zFunc(xMin, yMin);
    for (var x = xMin; x <= xMax; x += xStep)
    {
        for (var y = yMin; y <= yMax; y += yStep)
        {
            var z = zFunc(x,y);
            if (z < zSmallest) zSmallest = z;
            if (z > zBiggest)  zBiggest  = z;
        }
    }
    let zMin = zSmallest;
    let zMax = zBiggest;
    return [zMin, zMax]
}

/**
 *
 * @param {String} x function text of variables u and v to define x
 * @param {String} y function text of variables u and v to define y
 * @param {String} z function text of variables u and v to define z
 * @param {Object} view The mathbox cartesian to add the graph to
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
const createParametricGraph = (x, y, z, view, options = {}) => {
    options = {
        ...standardValues,
        ...options
    }
    let ranges = options.ranges.split(';').map(num => parseFloat(num));
    let resolution = options.resolution

    var	xMin = parseFloat(ranges[0]), xMax = parseFloat(ranges[1]), yMin = parseFloat(ranges[2]),
        yMax = parseFloat(ranges[3]), zMin = -3, zMax = 3;

    var xFunc = Parser.parse(x).toJSFunction(['u', 'v']);
    var yFunc = Parser.parse(y).toJSFunction(['u', 'v']);
    var zFunc = Parser.parse(z).toJSFunction(['u', 'v']);

    // Find the smallest and largest values of z in the range unless overridden
    if (ranges < 5) {
        [zMin, zMax] = calculateZRange(zFunc, ranges, resolution).map(num => parseFloat(num));
    } else {
        [zMin, zMax] = ranges.slice(-2)
    }

    var graphColorFunc = createGraphColorFunc(zFunc, zMin, zMax, options);

    var graphData = view.area({
        axes: [1,3], channels: 3, width: resolution, height: resolution,
        expr:   function (emit, u, v, i, j, t)
        {
            emit( xFunc(u,v), zFunc(u,v), yFunc(u,v) );
        },
    });

    var graphColors = view.area({
        rangeX: [xMin, xMax],
        rangeY: [yMin, yMax],
        expr: graphColorFunc,
        axes: [1,3],
        width:  resolution, height: resolution,
        channels: 4, // RGBA
    });

    // create graph in two parts, because want solid and wireframe to be different colors
    // shaded:false for a solid color (curve appearance provided by mesh)
    // width: width of line mesh
    // note: colors will mult. against color value, so set color to white (#FFFFFF) to let colors have complete control.
    var graphViewSolid = view.surface({
        points:graphData,
        color:"#FFFFFF", shaded:false, fill:true, lineX:false, lineY:false, colors:graphColors, visible:true, width:0
    });

    var graphWireVisible = true;
    var graphViewWire = view.surface({
        points: graphData,
        color:"#000000", shaded:false, fill:false, lineX:true, lineY:true, visible:graphWireVisible, width:1
    });

    return [zMin, zMax]
}

/**
 * Subfunction of createGraph used when it is determined to be an implicit function
 *
 * @param {String} exp The equation/function as a string
 * @param {Object} view The mathbox cartesian to add the graph to
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
const createImplicitGraph = (exp, view, options) => {
    options = {
        ...standardValues,
        ...options
    }
    // Validate inputs and create the function
    let resolution = options.resolution;
    let ranges = options.ranges.split(';').map(num => parseFloat(num));
    if (ranges.length < 5) {
        ranges = [ranges[0] ?? -3, ranges[1] ?? 3, ranges[2] ?? -3, ranges[3] ?? 3, ranges[4] ?? -3, ranges[5] ?? 3]
    }
    let functions = (''+exp).replace('==', "=").split('=');
    let lhs = Parser.parse(functions[0]);
    let rhs = Parser.parse(functions[1]);
    var frankenstein = lhs + '-' + rhs
    var frankenfunc = Parser.parse(frankenstein).toJSFunction(['x', 'z', 'y'])

    console.log(ranges)
    // Create the datapoints and insure they aren't too high for mathbox to handle
    let implicitTriangles = marchingCubes(ranges[0], ranges[1],
        ranges[2], ranges[3], ranges[4], ranges[5],
        frankenfunc, 0, resolution);
    //As mentioned in math3d react, with too many samples it might break
    var i = 1
    while(implicitTriangles.length > 5400) {
        console.log(implicitTriangles.length)
        implicitTriangles = marchingCubes(ranges[0], ranges[1],
            ranges[2], ranges[3], ranges[4], ranges[5],
            frankenfunc,0, (resolution/(2*i)))
        i++;
    }

    // Now we create the group and draw the graph
    var implicitgroup = view.group({
        visible: true,
    })

    var points = implicitgroup.array({
        items: 3,
        channels: 3,
        data: implicitTriangles,
        width: implicitTriangles.length,
        live: false
    });

    var surf = implicitgroup.strip({
        zOrder: 0,
        color: "#3090FF",
        opacity: 1,
        zIndex: 0,
        zBias: 0,
        shaded: true,
        fill:true,
    });

    return [ranges[4], ranges[5]]
}

/**
 * Subfunction of createGraph used when it is determined to be an explicit function
 *
 * @param {String} exp The equation/function as a string
 * @param {Object} view The mathbox cartesian to add the graph to
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
const createExplicitGraph = (exp, view, options = {}) => {
    options = {
        ...standardValues,
        ...options
    }
    let resolution = options.resolution;
    let ranges = options.ranges.split(';').map(num => parseFloat(num));

    var functionText = exp;

    var	xMin = parseFloat(ranges[0]), xMax = parseFloat(ranges[1]), yMin = parseFloat(ranges[2]),
        yMax = parseFloat(ranges[3]), zMin = -3, zMax = 3;

    var zFunc = Parser.parse( functionText ).toJSFunction( ['x','y'] );

    // Find the smallest and largest values of z in the range unless the range is overridden
    if (ranges.length < 5) {
        [zMin, zMax] = calculateZRange(zFunc, ranges, resolution).map(num => parseFloat(num))
    } else {
        [zMin, zMax] = ranges.slice(-2)
    }

    var graphColorFunc = createGraphColorFunc(zFunc, zMin, zMax, options);

    var graphData = view.area({
        axes: [1,3], channels: 3, width: resolution, height: resolution,
        expr:   function (emit, x, y, i, j, t)
        {
            emit( x, zFunc(x,y), y );
        },
    });

    var graphColors = view.area({
        expr: graphColorFunc,
        axes: [1,3],
        width:  resolution, height: resolution,
        channels: 4, // RGBA
    });

    // create graph in two parts, because want solid and wireframe to be different colors
    // shaded:false for a solid color (curve appearance provided by mesh)
    // width: width of line mesh
    // note: colors will mult. against color value, so set color to white (#FFFFFF) to let colors have complete control.
    var graphViewSolid = view.surface({
        points:graphData,
        color:"#FFFFFF", shaded:false, fill:true, lineX:false, lineY:false, colors:graphColors, visible:true, width:0
    });

    var graphWireVisible = true;
    var graphViewWire = view.surface({
        points: graphData,
        color:"#000000", shaded:false, fill:false, lineX:true, lineY:true, visible:graphWireVisible, width:1
    });

    return [zMin, zMax]
}


/**
 * Generates color-style based on preset styles specified in the options parameter
 * @param zFunc A mathematical function parsed to javascript, currently only two variables supported
 * @param {number} zMin the z-value of the graphs lowest point
 * @param {number} zMax the z-value of the graphs highest point
 * @param {Object|null} options Any optional changes, @see {@link standardValues} to see the supported options
 */
export const createGraphColorFunc = (zFunc, zMin, zMax, options = {}) => {
    let graphopacity = options.graphopacity;

    var graphColorFunc, graphColorStyle = options['graphcolorstyle'];

    if (graphColorStyle == 'grayscale') {
        graphColorFunc = (emit, x, y, i, j, t) => {
            var z = zFunc(x,y);
            var percent = (z - zMin) / (zMax - zMin);
            emit( percent, percent, percent, graphopacity ?? 1.0 );
        }
    } else if (graphColorStyle == 'rainbow') {
        graphColorFunc = (emit, x, y, i, j, t) => {
            var z = zFunc(x,y);
            var percent = (z - 1.2 * zMin) / (zMax - 1.2 * zMin);
            var color = new THREE.Color( 0xffffff );
            color.setHSL( 1-percent, 1, 0.5 );
            emit( color.r, color.g, color.b, graphopacity ?? 0.8 );
        }
    } else if (graphColorStyle == 'lightblue') {
        graphColorFunc = (emit, x, y, i, j, t) => {
            emit(0, 0.6, 0.9, graphopacity ?? 0.9);
        };
    } else if (graphColorStyle == 'solidblue') {
        graphColorFunc = (emit, x, y, i, j, t) => {
            emit( 0.5, 0.5, 1.0, graphopacity ?? 1.0 );
        };
    }
    return graphColorFunc;
}

/**
 * Creates references tied to the window object to allow non-module scripts to use its functions
 */
export const initMethods = () => {
    var threed = {};
    threed['createParametricGraph'] = createParametricGraph;
    threed['createExplicitGraph'] = createExplicitGraph;
    threed['createImplicitGraph'] = createImplicitGraph;

    threed['createMathbox'] = createMathBox;
    threed['createGraph'] = createGraph;
    threed['createGraphColorFunc'] = createGraphColorFunc;
    window['threed'] = threed;
}
