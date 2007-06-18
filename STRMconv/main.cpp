/*
Convert STRM files to VisuGps format.
This application has been developped with Ultimate++ <http://www.ultimatepp.org/>

STRM files can be found at <http://srtm.csi.cgiar.org/index.asp>

Author:
	Victor Berchet
	
History:
	Jun 18, 2007: Initial revision	
	
*/

#include "STRMconv.h"

STRMconv::STRMconv()
{
	CtrlLayout(*this, "STRM to VisuGPS converter");

	btnConvert <<= THISBACK(Convert);
	btnOpen <<= THISBACK(Open);
	
	fs.Multi(false)
	  .NoEditFileName()
      .Type("STRM files (*.asc)", "*.asc")
      .Type("STRMB file (*.strmb)", "*.strmb")
      .Type("All Files (*.*)", "*.*");
}


void STRMconv::ComputeImg(void)
{
	ImageBuffer ib(previewSize, previewSize);
	
	for(int y = 0; y < previewSize; y++) {
		RGBA *l = ib[y];
		for(int x = 0; x < previewSize; x++) {			
			int step = strmSize / previewSize;
			byte height = min(raster.At(strmSize * step * y + step * x) / 15 + 40, 255);
			l->a = 255;
			l->r = height;
			l->g = height;
			l->b = height;
			l++;
		}
	}

	img.SetImage(ib);

}

void STRMconv::Open(void)
{
	fs.ActiveType(0);
    if (!fs.ExecuteOpen("Select a STRM file")) {
    	return;
    } else {        
    	txt.SetText(~fs);  
    	if (ToLower(GetFileExt(~fs)) == ".asc") {
    		btnConvert.SetLabel("Convert");
    	} else {
    		btnConvert.SetLabel("Display");
    	}
    }    			
}

void STRMconv::StoreStrm(void)
{
	if (raster.GetCount() == strmSize * strmSize) {
		
		String fName = txt.GetText();
		
		if (!IsNull(llCorner.x) && !IsNull(llCorner.y)) {
			fName = ToLower(GetFileDirectory(fName)) + 
					Format("strm3_%d_%d.strmb", llCorner.y, llCorner.x);
		} else {
			fName = ToLower(GetFileDirectory(fName) + 
							GetFileTitle(fName) + ".strmb");		
		}
				
		FileOut f(fName);
		
		Progress progress(this, "Storing the output file...", strmSize * strmSize);			
		
		for (int i = 0; i < strmSize * strmSize; i++) {
			int val;
			if (raster[i] <= 0) {
				val = 0;
			} else {
				val = ((raster[i] + (elevFactor / 2)) / elevFactor) + 1;
				val = minmax(val, 0, 255);
			}
			f.Put(val);
			progress.Step();
		}
		
		progress.Hide();
		f.Close();
	}
}

void STRMconv::Convert(void)
{
	String fName = txt.GetText();
	
	llCorner.SetNull();
	
	if (ToLower(GetFileExt(fName)) == ".asc") {

		if (FileExists(fName)) {
			FileIn file(fName);		
			String s;
			
			raster.Clear();
			
			// Process STRM header 
			for (int i = 0; i < 6; i++) {				
				Vector<String> fields = Split(file.GetLine(), ' ');
				if (fields.GetCount() >= 2) {				
					if (ToUpper(fields[0]) == "XLLCORNER") {
						llCorner.x = ScanInt(fields[1]);
					} else if (ToUpper(fields[0]) == "YLLCORNER") {
						llCorner.y = ScanInt(fields[1]);
					}					
				}			
			}
					
			Progress progress(this, "Reading the input file...", strmSize * strmSize);					
					
			while (!file.IsEof()) {
				int b = file.Get();
				if (b == ' ') {			
					short val = StrInt(s);
					if (val < 0) val = 0;
					raster.Add(val);
					s = "";
					progress.Step();
				} else {
					s += b;					
				}
			}

			progress.Hide();
			file.Close();
			
			ComputeImg();
			StoreStrm();
		}
	} else if (ToLower(GetFileExt(fName)) == ".strmb") {
		
		raster.Clear();
		
		if (FileExists(fName)) {
			FileIn file(fName);
			
			Progress progress(this, "Reading the input file...", strmSize * strmSize);
			
			while (!file.IsEof()) {
				raster.Add(file.Get() * elevFactor);
				progress.Step();
			}
			
			progress.Hide();
			file.Close();
			
			if (raster.GetCount() == strmSize * strmSize) {
				ComputeImg();
			}		
		}
	}
}

GUI_APP_MAIN
{
	STRMconv conv;
	LoadFromFile(conv);
	conv.Run();
	StoreToFile(conv);
}

